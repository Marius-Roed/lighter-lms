<script>
    import Switch from "$components/Switch.svelte";
    import importManager from "$lib/import.svelte";
    import lighterFetch from "$lib/lighterFetch";
    import settings from "$lib/settings.svelte";

    let importing = false;
    /** @type {FileList | undefined} */
    let files = $state();
    /** @type {File} */
    let selectedFile = $state();
    let firstHeader = $state(true);
    let createOrders = $state(false);
    let userName = $state(true);
    let skipNew = $state(false);
    let notify = $state(true);
    let separator = $state(",");
    let rows = $state([]);

    const fields = {
        fname: { label: "First name", always: true },
        lname: { label: "Last name", always: true },
        email: { label: "Email", always: true },
        phone: { label: "phone", always: true },
        username: { label: "Username", when: () => !userName },
        courses: { label: "Courses", when: () => !createOrders },
        products: { label: "Product SKUs", when: () => createOrders },
        address: { label: "Address", when: () => createOrders },
        city: { label: "City", when: () => createOrders },
        postcode: { label: "Postcode", when: () => createOrders },
        country: { label: "Country", when: () => createOrders },
        orderNodes: { label: "Notes", when: () => createOrders },
        creationDate: { label: "Date created", when: () => createOrders },
    };
    const activeFields = $derived(
        Object.entries(fields)
            .filter(([_, def]) => def.always || (def.when && def.when()))
            .map(([key]) => key),
    );

    const headers = $derived(
        Object.fromEntries(activeFields.map((key) => [key, fields[key].label])),
    );

    /**
     * @type {(sep: string, header: boolean, full?: boolean) => Promise}
     */
    const parseCSV = async (sep, header, full) => {
        if (!selectedFile) {
            rows = [];
            return;
        }

        try {
            const text = await selectedFile.text();
            const lines = text
                .split("\n")
                .map((l) => l.trim())
                .filter(Boolean);
            const start = header ? 1 : 0;

            if (full) {
                return Promise.resolve(
                    lines.slice(start, -1).map((line) => {
                        const values = line.split(sep).map((v) => v.trim());
                        const row = {};
                        activeFields.forEach((key, idx) => {
                            row[key] = values[idx] ?? "";
                        });

                        return row;
                    }),
                );
            }

            const preview = lines.slice(start, start + 5);

            rows = preview.map((line) => {
                const values = line.split(sep).map((v) => v.trim());
                const row = {};
                activeFields.forEach((key, idx) => {
                    row[key] = values[idx] ?? "";
                });

                return row;
            });
        } catch (e) {
            console.error("Error parsing csv", e);
            rows = [];
        }
    };

    /**
     * @type {(e: Event) => Promise}
     */
    const handleImport = async (e) => {
        e.preventDefault();
        if (importing) return;

        if (!selectedFile) {
            // TODO: Toast "no csv found" error
            console.error("no csv found");
            return;
        }
        importing = true;
        const csv = await parseCSV(separator, firstHeader, true);

        if (!csv || !csv[0].email) {
            // TODO: Show error state
            return;
        }

        try {
            const formdata = new FormData();
            formdata.append("file", selectedFile);
            formdata.append(
                "options",
                JSON.stringify({
                    options: {
                        separator,
                        firstHeader,
                        createOrders,
                        userName,
                        skipNew,
                        notify,
                    },
                }),
            );

            const res = await lighterFetch({
                path: "import",
                method: "POST",
                body: formdata,
            });

            importManager.addJob(res.job);

            let notifBtn = document.getElementById("lighter-notifs-panel");
            notifBtn?.showPopover();
        } catch (e) {
            // TODO: Show fail
            console.error("Could not start import job", e);
        } finally {
            importing = false;
        }
    };

    $effect(() => {
        activeFields;
        if (files?.[0]) {
            selectedFile = files[0];
        }
        parseCSV(separator, firstHeader);
    });
</script>

<div class="grid bordered">
    <h2>Import users</h2>
    <p>Import users and give them their appropriate courses.</p>
    <div class="example">
        {#if selectedFile}
            <p>File: {selectedFile.name}</p>
            Preview:
        {:else}
            <p>Example CSV file:</p>
        {/if}
        <table>
            <thead>
                <tr>
                    {#each Object.values(headers) as header}
                        <td>{header}</td>
                    {/each}
                </tr>
            </thead>
            <tbody>
                {#if rows.length}
                    {#each rows as row}
                        <tr>
                            {#each Object.keys(headers) as key}
                                <td>{row[key]}</td>
                            {/each}
                        </tr>
                    {/each}
                {:else}
                    <tr>
                        <td>John</td>
                        <td>Smith</td>
                        <td>john.smith@{window.location.hostname}</td>
                        <td>+1-123-555-1234</td>
                        <td>143 152 98 120</td>
                        {#if !userName}<td>John.Smith</td>{/if}
                        {#if createOrders}
                            <td>123 Main St</td>
                            <td>New York</td>
                            <td>10001</td>
                            <td>USA</td>
                            <td>(Old order #)35134</td>
                            <td>{new Date("1").toISOString()}</td>
                        {/if}
                    </tr>
                    <tr>
                        <td>Jane</td>
                        <td>Doe</td>
                        <td>jane.doe@{window.location.hostname}</td>
                        <td>+1-321-555-4321</td>
                        <td>123</td>
                        {#if !userName}<td>Jane.Doe</td>{/if}
                        {#if createOrders}
                            <td>456 Oak Ave</td>
                            <td>Boston</td>
                            <td>02101</td>
                            <td>USA</td>
                            <td>I will show up in the order notes</td>
                            <td>{new Date().toISOString()}</td>
                        {/if}
                    </tr>
                {/if}
            </tbody>
        </table>
    </div>

    <fieldset>
        <legend><h3>Options</h3></legend>
        <div class="grid-2">
            <Switch
                onLabel="Use first row as header row"
                name="first_header"
                bind:checked={firstHeader}
            />
            <Switch
                onLabel="Skip new users"
                name="skip_new"
                bind:checked={skipNew}
            />
            <Switch
                onLabel="Use email as user login"
                name="login_email"
                bind:checked={userName}
            />
            <Switch
                onLabel="Notify users"
                name="notify"
                bind:checked={notify}
            />
            <Switch
                onLabel={"Create order in " + (settings.store ?? "store")}
                name="orders"
                bind:checked={createOrders}
            />
        </div>
        <div class="separator-group">
            <p>Separator</p>
            <label>
                <input
                    type="radio"
                    name="separator"
                    value=","
                    bind:group={separator}
                />
                Comma (,)
            </label>
            <label>
                <input
                    type="radio"
                    name="separator"
                    value="."
                    bind:group={separator}
                />
                Period (.)
            </label>
            <label>
                <input
                    type="radio"
                    name="separator"
                    value=":"
                    bind:group={separator}
                />
                Colon (:)
            </label>
            <label>
                <input
                    type="radio"
                    name="separator"
                    value=";"
                    bind:group={separator}
                />
                Semicolon (;)
            </label>
            <label>
                <input
                    type="radio"
                    name="separator"
                    value=" "
                    bind:group={separator}
                />
                Space ( )
            </label>
        </div>
    </fieldset>

    <div class="file-input">
        <input
            type="file"
            name="import-user"
            id="user-import"
            accept=".csv"
            bind:files
        />
    </div>
    <div class="submit">
        <button type="button" class="lighter-btn" onclick={handleImport}
            >Import</button
        >
    </div>
</div>

<div class="grid bordered">
    <h2>Export users</h2>
    <p>Generate a CSV-file with your users data.</p>
</div>
