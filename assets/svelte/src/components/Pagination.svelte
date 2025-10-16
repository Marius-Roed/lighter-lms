<script>
    import Icon from "./Icon.svelte";

    let { currentPage = $bindable(1), totalPages, onPageChange } = $props();

    const visiblePages = $derived.by(() => {
        const delta = 2;
        const range = [];
        const rangeWithDots = [];

        for (
            let i = Math.max(2, currentPage - delta);
            i <= Math.min(totalPages - 1, currentPage + delta);
            i++
        ) {
            range.push(i);
        }

        if (currentPage - delta > 2) {
            rangeWithDots.push(1, "...");
        } else {
            rangeWithDots.push(1);
        }

        rangeWithDots.push(...range);

        if (currentPage + delta < totalPages - 1) {
            rangeWithDots.push("...", totalPages);
        } else {
            rangeWithDots.push(totalPages);
        }

        return rangeWithDots;
    });

    function goToPage(page) {
        if (page >= 1 && page <= totalPages && page !== currentPage) {
            onPageChange(page);
        }
    }

    function goToPrev() {
        currentPage--;
        onPageChange(currentPage);
    }

    function goToNext() {
        currentPage++;
        onPageChange(currentPage);
    }
</script>

<nav class="pagination" aria-label="Pagination navigation">
    <button
        type="button"
        class="lighter-btn transparent wp-background"
        onclick={goToPrev}
        disabled={currentPage <= 1}
        aria-label="Go to previous page"
        ><Icon name="chevron" size="0.75em" /></button
    >
    {currentPage} of {totalPages}
    <button
        type="button"
        class="lighter-btn transparent wp-background"
        onclick={goToNext}
        disabled={currentPage >= totalPages}
        aria-label="Go to next page"
        ><Icon name="chevron" size="0.75em" /></button
    >
</nav>
