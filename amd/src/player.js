// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Player for a practice series (UC3, fase 5).
 *
 * Every call to init() works only inside its own root element, so several players can sit on one
 * page (UC14, UC15). The player measures how much of an item the student has really heard: only
 * stretches that played without a jump count, so seeking to the end does not count. Once the share
 * reaches the threshold (75%), Moodle records the item as listened to.
 *
 * @module     mod_mediaspeler/player
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {getString} from 'core/str';

/** Largest step between two timeupdate events that still counts as continuous playing, in seconds. */
const MAX_STEP = 2;

/**
 * Total length covered by a list of [start, end] stretches, overlaps counted once.
 *
 * @param {Array<Array<number>>} stretches
 * @returns {number} Seconds.
 */
const coveredSeconds = (stretches) => {
    const sorted = stretches.slice().sort((a, b) => a[0] - b[0]);
    let total = 0;
    let current = null;
    sorted.forEach(([start, end]) => {
        if (current === null || start > current[1]) {
            if (current !== null) {
                total += current[1] - current[0];
            }
            current = [start, end];
        } else if (end > current[1]) {
            current[1] = end;
        }
    });
    if (current !== null) {
        total += current[1] - current[0];
    }
    return total;
};

/**
 * One player on the page.
 */
class Player {
    /**
     * @param {HTMLElement} root The root element of this player.
     */
    constructor(root) {
        this.root = root;
        this.cmid = parseInt(root.dataset.cmid, 10);
        this.threshold = parseFloat(root.dataset.threshold) || 0.75;
        this.audio = root.querySelector('[data-region="audio"]');
        this.nowTitle = root.querySelector('[data-region="now-title"]');
        this.progress = root.querySelector('[data-region="progress"]');
        this.progressText = root.querySelector('[data-region="progress-text"]');
        /** @type {Map<number, Array<Array<number>>>} Heard stretches per item, for this page visit. */
        this.heard = new Map();
        /** @type {Set<number>} Items for which a request to Moodle is under way. */
        this.pending = new Set();
        this.itemid = parseInt(this.audio.dataset.itemid, 10);
        this.last = null;

        this.root.addEventListener('click', (e) => {
            const button = e.target.closest('[data-action="select"]');
            if (button && this.root.contains(button)) {
                e.preventDefault();
                this.select(button.closest('[data-region="item"]'), true);
            }
        });

        this.audio.addEventListener('play', () => {
            this.last = this.audio.currentTime;
        });
        this.audio.addEventListener('seeking', () => {
            this.last = null;
        });
        this.audio.addEventListener('seeked', () => {
            this.last = this.audio.paused ? null : this.audio.currentTime;
        });
        this.audio.addEventListener('pause', () => {
            this.record();
            this.last = null;
        });
        this.audio.addEventListener('timeupdate', () => this.record());
        this.audio.addEventListener('ended', () => {
            this.record();
            this.last = null;
        });
        this.audio.addEventListener('loadedmetadata', () => this.showProgress());

        this.markCurrent();
        this.showProgress();
    }

    /**
     * The list element of an item.
     *
     * @param {number} itemid
     * @returns {HTMLElement|null}
     */
    itemElement(itemid) {
        return this.root.querySelector(`[data-region="item"][data-itemid="${itemid}"]`);
    }

    /**
     * Loads another item into the player.
     *
     * @param {HTMLElement} element The list element of the item.
     * @param {boolean} play Start playing straight away.
     */
    select(element, play) {
        const itemid = parseInt(element.dataset.itemid, 10);
        if (itemid !== this.itemid) {
            this.record();
            this.last = null;
            this.itemid = itemid;
            this.audio.dataset.itemid = String(itemid);
            this.audio.src = element.dataset.src;
            this.nowTitle.textContent = element.dataset.title;
            this.markCurrent();
            this.showProgress();
        }
        if (play) {
            this.audio.play().catch(() => {
                // The browser refused to start without a user gesture: the controls stay available.
            });
        }
    }

    /**
     * Highlights the item that is loaded.
     */
    markCurrent() {
        this.root.querySelectorAll('[data-region="item"]').forEach((element) => {
            const current = parseInt(element.dataset.itemid, 10) === this.itemid;
            element.classList.toggle('active', current);
            const button = element.querySelector('[data-action="select"]');
            if (current) {
                element.setAttribute('aria-current', 'true');
                button.classList.add('text-white');
            } else {
                element.removeAttribute('aria-current');
                button.classList.remove('text-white');
            }
        });
    }

    /**
     * Adds the stretch played since the last event to what the student has heard.
     */
    record() {
        const now = this.audio.currentTime;
        if (this.last !== null && !this.audio.seeking) {
            const step = now - this.last;
            const maxstep = MAX_STEP * Math.max(1, this.audio.playbackRate || 1);
            if (step > 0 && step <= maxstep) {
                if (!this.heard.has(this.itemid)) {
                    this.heard.set(this.itemid, []);
                }
                this.heard.get(this.itemid).push([this.last, now]);
            }
        }
        this.last = this.audio.paused ? null : now;
        this.showProgress();
        this.checkListened();
    }

    /**
     * Share of the current item heard during this visit, between 0 and 1.
     *
     * @returns {number}
     */
    share() {
        const duration = this.audio.duration;
        if (!isFinite(duration) || duration <= 0) {
            return 0;
        }
        return Math.min(1, coveredSeconds(this.heard.get(this.itemid) || []) / duration);
    }

    /**
     * Shows how much of the current item has been heard.
     */
    showProgress() {
        const element = this.itemElement(this.itemid);
        const done = element && element.dataset.listened === '1';
        const percent = done ? 100 : Math.floor(this.share() * 100);
        this.progress.style.width = `${percent}%`;
        this.progress.setAttribute('aria-valuenow', String(percent));
        this.progress.classList.toggle('bg-success', done);
        this.progressText.textContent = `${percent}%`;
    }

    /**
     * Tells Moodle once the student has heard enough of the current item.
     */
    checkListened() {
        const itemid = this.itemid;
        const element = this.itemElement(itemid);
        if (!element || element.dataset.listened === '1' || this.pending.has(itemid)) {
            return;
        }
        if (this.share() < this.threshold) {
            return;
        }
        this.pending.add(itemid);
        // Moodle's Ajax returns a jQuery promise; wrap it to get a native one with finally().
        Promise.resolve(Ajax.call([{
            methodname: 'mod_mediaspeler_mark_listened',
            args: {cmid: this.cmid, itemid: itemid},
        }])[0]).then((result) => {
            element.dataset.listened = '1';
            const badge = element.querySelector('[data-region="listened"]');
            badge.hidden = false;
            this.showProgress();
            if (result.groupmates > 0) {
                // UC16: tell the student it counted for the groupmates too.
                return getString('listenedtogether', 'mod_mediaspeler', result.groupmates).then((text) => {
                    const note = document.createElement('span');
                    note.className = 'visually-hidden-focusable d-block small fw-normal';
                    note.textContent = text;
                    badge.title = text;
                    badge.append(note);
                    return null;
                });
            }
            return null;
        }).catch(Notification.exception).finally(() => {
            this.pending.delete(itemid);
        });
    }
}

/**
 * Starts the player in the element with this id.
 *
 * @param {string} rootid Id of the player's root element.
 */
export const init = (rootid) => {
    const root = document.getElementById(rootid);
    if (root && !root.dataset.initialised) {
        root.dataset.initialised = '1';
        new Player(root);
    }
};
