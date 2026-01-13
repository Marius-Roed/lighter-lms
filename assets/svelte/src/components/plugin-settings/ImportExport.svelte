<script>
    import Switch from "$components/Switch.svelte";
    import settings from "$lib/settings.svelte";

    /** @type {FileList | undefined} */
    let files = $state();
    let firstHeader = $state(true);
    let createOrders = $state(false);
    let userName = $state(true);
    let separator = $state(",");
    let rows = $state([]);

    const baseHeaders = {
        fname: "First name",
        lname: "Last name",
        email: "Email",
        phone: "Phone",
        courses: "Courses",
    };
    const orderHeaders = {
        address: "Address",
        city: "City",
        postcode: "Postcode",
        country: "Country",
    };

    const headers = $derived({
        ...baseHeaders,
        ...(userName ? {} : { username: "User name" }),
        ...(createOrders ? orderHeaders : {}),
    });

    const parseCSV = async (sep, header) => {
        const file = files?.[0];
        if (!file) {
            rows = [];
            return;
        }

        try {
            const text = await file.text();
            const lines = text.split("\n").filter((line) => line.trim());
            const dataRows = header ? lines.slice(1, 6) : lines.slice(0, 5);

            rows = dataRows.map((line) => {
                const values = line.split(sep).map((v) => v.trim());
                return {
                    fname: values[0] || "",
                    lname: values[1] || "",
                    uname: values[2] || "",
                    email: values[3] || "",
                    phone: values[4] || "",
                    courses: values[5] || "",
                    address: values[6] || "",
                    city: values[7] || "",
                    postcode: values[8] || "",
                    country: values[9] || "",
                };
            });
        } catch (e) {
            console.error("Error parsing csv", e);
            rows = [];
        }
    };

    $effect(() => {
        if (files?.[0]) parseCSV(separator, firstHeader);
    });
</script>

<div class="grid bordered">
    <h2>Import users</h2>
    <p>Import users and give them their appropriate courses.</p>
    <div class="example">
        <p>Example CSV file:</p>
        {#if files}
            File: {files[0].name}
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
            <Switch onLabel="Skip new users" name="skip_new" />
            <Switch
                onLabel="Use email as user login"
                name="login_email"
                bind:checked={userName}
            />
            <Switch onLabel="Notify users" name="notify" checked />
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
        <input type="submit" class="lighter-btn" value="import" />
    </div>
</div>

<div class="grid bordered">
    <h2>Export users</h2>
    <p>Generate a CSV-file with your users data.</p>
</div>
