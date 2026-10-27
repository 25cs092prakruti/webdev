
export async function initDirectory(config) {
    const {
        key,
        file,
        titleField,
        detailFields = [],
        searchFields = [],
        filterField,
        pageSize = 5
    } = config;

    const search = document.getElementById(`${key}-search`);
    const filter = document.getElementById(`${key}-filter`);
    const sort = document.getElementById(`${key}-sort`);
    const list = document.getElementById(`${key}-list`);
    const message = document.getElementById(`${key}-message`);
    const pagination = document.getElementById(`${key}-pagination`);
    const count = document.getElementById(`${key}-count`);

    // Check that the required HTML elements exist.
    if (!search || !filter || !sort || !list ||
        !message || !pagination || !count) {
        console.error(`Missing HTML elements for ${key}.`);
        if (message) {
            message.textContent =
                `Page setup error: check the HTML IDs for ${key}.`;
            message.className = "message error";
        }
        return;
    }

    let records = [];
    let currentPage = 1;

    function render() {
        const query = search.value.trim().toLowerCase();
        const selectedFilter = filter.value;

        let filtered = records.filter(record => {
            const matchesSearch = searchFields.some(field =>
                String(record[field] ?? "")
                    .toLowerCase()
                    .includes(query)
            );

            const matchesFilter =
                selectedFilter === "" ||
                String(record[filterField] ?? "") === selectedFilter;

            return matchesSearch && matchesFilter;
        });

        // Sort records.
        const sortParts = sort.value.split("-");
        const direction = sortParts.pop();
        const sortField = sortParts.join("-");

        filtered.sort((a, b) => {
            const first = a[sortField];
            const second = b[sortField];

            let result;

            if (typeof first === "number" &&
                typeof second === "number") {
                result = first - second;
            } else {
                result = String(first ?? "").localeCompare(
                    String(second ?? ""),
                    undefined,
                    { numeric: true, sensitivity: "base" }
                );
            }

            return direction === "desc" ? -result : result;
        });

        const totalPages = Math.max(
            1,
            Math.ceil(filtered.length / pageSize)
        );

        currentPage = Math.min(currentPage, totalPages);

        const start = (currentPage - 1) * pageSize;
        const pageRecords = filtered.slice(start, start + pageSize);

        list.replaceChildren();

        pageRecords.forEach(record => {
            const card = document.createElement("article");
            card.className = "record";

            const heading = document.createElement("h3");
            heading.textContent = record[titleField] ?? "Untitled";
            card.appendChild(heading);

            detailFields.forEach(field => {
                const paragraph = document.createElement("p");
                const label = document.createElement("strong");

                label.textContent = `${field.label}: `;

                let value = record[field.key];

                if (field.key === "active") {
                    value = value ? "Active" : "Inactive";
                }

                paragraph.append(
                    label,
                    document.createTextNode(String(value ?? "—"))
                );

                card.appendChild(paragraph);
            });

            list.appendChild(card);
        });

        count.textContent = `${filtered.length} records`;

        message.className = "message";
        message.textContent = filtered.length
            ? `Showing ${start + 1}–${Math.min(
                start + pageSize,
                filtered.length
            )} of ${filtered.length} records`
            : "No matching records found.";

        // Pagination buttons.
        pagination.replaceChildren();

        if (filtered.length > 0) {
            const previous = document.createElement("button");
            previous.type = "button";
            previous.textContent = "Previous";
            previous.disabled = currentPage === 1;

            previous.addEventListener("click", () => {
                if (currentPage > 1) {
                    currentPage--;
                    render();
                }
            });

            const pageLabel = document.createElement("span");
            pageLabel.textContent =
                `Page ${currentPage} of ${totalPages}`;

            const next = document.createElement("button");
            next.type = "button";
            next.textContent = "Next";
            next.disabled = currentPage === totalPages;

            next.addEventListener("click", () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    render();
                }
            });

            pagination.append(previous, pageLabel, next);
        }
    }

    function populateFilter() {
        // Preserve the first option, such as "All Departments".
        filter.length = 1;

        const values = [
            ...new Set(
                records
                    .map(record => record[filterField])
                    .filter(value =>
                        value !== null &&
                        value !== undefined &&
                        String(value).trim() !== ""
                    )
                    .map(String)
            )
        ].sort((a, b) => a.localeCompare(b));

        values.forEach(value => {
            const option = document.createElement("option");
            option.value = value;
            option.textContent = value;
            filter.appendChild(option);
        });
    }

    function showError(error) {
        console.error(`Error loading ${key}:`, error);

        message.textContent =
            `Unable to load ${key}. Check the JSON file and browser Console.`;
        message.className = "message error";

        list.replaceChildren();
        pagination.replaceChildren();
        count.textContent = "Unavailable";
    }

    // Search, filter and sorting listeners.
    search.addEventListener("input", () => {
        currentPage = 1;
        render();
    });

    filter.addEventListener("change", () => {
        currentPage = 1;
        render();
    });

    sort.addEventListener("change", () => {
        currentPage = 1;
        render();
    });

    // Load JSON records.
    try {
        message.textContent = `Loading ${key}...`;

        const response = await fetch(file);

        if (!response.ok) {
            throw new Error(
                `HTTP ${response.status}: ${file} could not be loaded`
            );
        }

        const data = await response.json();

        if (!Array.isArray(data)) {
            throw new Error(`${file} must contain a JSON array.`);
        }

        records = data;
        populateFilter();
        render();

    } catch (error) {
        showError(error);
    }
}
