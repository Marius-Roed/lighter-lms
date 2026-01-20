<script>
    import importManager from "$lib/import.svelte";
    import Icon from "./Icon.svelte";

    const formatProgress = (/** @type {number} */ progress) => {
        return progress.toFixed().slice(-2) == "00"
            ? progress
            : progress.toFixed(2);
    };

    const cancelJob = (/** @type {string} */ jobId) => {
        importManager.jobs.get(jobId).cancel();
    };

    const bell = $derived(
        importManager.getActive().length ? "bellFilled" : "bell",
    );
</script>

<button
    type="button"
    id="lighter-notif-btn"
    class={["lighter-notif-btn", importManager.getActive().length && "notify"]}
    popovertarget="lighter-notifs-panel"
>
    <Icon name={bell} size="1.5em" />
</button>

<div id="lighter-notifs-panel" popover="auto">
    {#each importManager.sortedJobs as job (job.id)}
        <div class="import-job">
            <div class="top-container">
                <div class="info">
                    <label for={job.id}>{job.filename}</label>
                    <p>{job.status}</p>
                </div>
                <div class="actions">
                    {#if job.status === "running" || job.status === "paused"}
                        <button
                            type="button"
                            class="pause-resume"
                            onclick={() =>
                                job.status === "running"
                                    ? importManager.jobs.get(job.id)?.pause()
                                    : importManager.jobs.get(job.id)?.resume()}
                        >
                            <Icon
                                name={job.status === "running"
                                    ? "pause"
                                    : "play"}
                            />
                        </button>
                        <button
                            type="button"
                            class="cancel"
                            onclick={() => cancelJob(job.id)}
                        >
                            <Icon name="cancel" />
                        </button>
                    {:else}
                        <button
                            type="button"
                            class="dismiss"
                            onclick={() => importManager.removeJob(job.id)}
                        >
                            <Icon name="plus" />
                        </button>
                    {/if}
                </div>
            </div>
            <div class="progress-container">
                <progress id={job.id} max="100" value={job.progress}></progress>
                <p>{formatProgress(job.progress ?? 0)}%</p>
            </div>
        </div>
    {:else}
        No new notifications
    {/each}
</div>
