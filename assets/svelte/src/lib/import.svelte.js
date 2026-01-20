import lighterFetch from "$lib/lighterFetch";
import { SvelteMap } from "svelte/reactivity";

const STORAGE_KEY = 'lighter-jobs';
const GLOBAL_KEY = '__lighter_import_manager__';

/**
 * @typedef {"failed"|"running"|"paused"|"cancelled"|"completed"} JobStatus
 */

class Job {
    /** @type {string} */
    id = $state();
    filename = $state('');
    /** @type {JobStatus} */
    status = $state('running');
    progress = $state(0);
    errors = $state([]);
    fails = $state(0);

    /** @type {AbortController} */
    #abortController = null;

    /**
    *
    */
    constructor(data) {
        this.id = data.id;
        this.filename = data.filename;
        this.status = data.status || "running";
        this.progress = data.progress || 0;
        this.errors = data.errors || [];
        this._init();
    }

    async _init() {
        if (this.status === "running") {
            await new Promise(r => setTimeout(r, 500));
            this.startPolling();
        }
    }

    async pause() {
        this.status = "paused";
        this.stopPolling();
        const res = lighterFetch({
            path: `import/${this.id}`,
            method: "PUT",
            body: JSON.stringify({
                paused: true,
            })
        });

        // TODO: Handle server res.
    }

    async resume() {
        this.status = "running";
        const res = lighterFetch({
            path: `import/${this.id}`,
            method: "PUT",
            body: JSON.stringify({
                resume: true,
            })
        });

        // TODO: Handle server res.
    }

    async cancel() {
        this.status = "cancelled"
        this.stopPolling();
        const res = lighterFetch({
            path: `import/${this.id}`,
            method: "PUT",
            body: JSON.stringify({
                cancel: true,
            })
        });


        // TODO: Handle server res.
    }

    startPolling() {
        if (this.#abortController) return;

        this.#abortController = new AbortController();
        const signal = this.#abortController.signal;

        this.#poll(signal);
    }

    stopPolling() {
        if (this.#abortController) {
            this.#abortController.abort();
            this.#abortController = null;
        }
    }

    /**
     * Polls for status
     * @param {AbortSignal} signal 
     */
    async #poll(signal) {
        while (this.status === "running" && !signal.aborted) {
            try {
                const res = await /** @type {Promise<Job>} */ (
                    lighterFetch({
                        path: `import/${this.id}`,
                        // data: { _t: Date.now() },
                        method: "GET",
                        signal,
                    })
                );

                this.status = res.status;
                this.progress = res.progress;
                this.errors = res.errors;

                if (this.status !== "running") {
                    this.stopPolling();
                }
            } catch (e) {
                if (e.name !== "AbortError") {
                    console.warn(`Error polling job "${this.id}":`, e.message);
                }
                await new Promise(r => setTimeout(r, 5000));
                this.fails++;
                if (this.fails >= 5) {
                    console.warn(`Failed to poll for job "${this.id}" cancelling.`);
                    break; // Do not run infinitely.
                }
                continue;
            }

            if (!signal.aborted) {
                await new Promise(r => setTimeout(r, 1500));
            }
        }
    }

    toJSON() {
        return {
            id: this.id,
            filename: this.filename,
            status: this.status,
            progress: this.progress,
            errors: this.errors
        };
    }
}

class ImportManager {
    jobs = new SvelteMap();

    sortedJobs = $derived(
        Array.from(this.jobs.values()).reverse()
    );

    constructor() {
        this.#restore();

        $effect.root(() => {
            $effect(() => {
                this.#save();
            });
        });
    }

    /**
     * @param {object} data
     */
    addJob(data) {
        if (this.jobs.has(data.id)) return;
        const job = new Job(data);
        this.jobs.set(data.id, job);
    }

    /**
     * @param {string} id 
     */
    removeJob(id) {
        /** @type {Job} */
        const job = this.jobs.get(id);
        if (job) {
            job.stopPolling();
            this.jobs.delete(id);
        }
    }

    getActive() {
        return this.sortedJobs.filter((/** @type {Job} */ j) => j.status === "running");
    }

    #save() {
        const payload = JSON.stringify(Array.from(this.jobs.values()));
        localStorage.setItem(STORAGE_KEY, payload);
    }


    #restore() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return;
            const items = JSON.parse(raw);

            items.forEach(data => {
                if (!this.jobs.has(data.id)) {
                    this.jobs.set(data.id, new Job(data));
                }
            });
        } catch (e) {
            console.error("Failed to restore imports:", e);
        }
    }
}

if (!window[GLOBAL_KEY]) {
    window[GLOBAL_KEY] = new ImportManager();
}

/** @type {ImportManager} */
const importManager = window[GLOBAL_KEY];
export default importManager;
