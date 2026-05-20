/*
  Admin Weekly Breakdown CRUD
*/


// --- Global Data Store ---

let weeks = [];


// --- Element Selections ---

const weekForm =
    document.getElementById("week-form");

const weeksTbody =
    document.getElementById("weeks-tbody");

const submitButton =
    document.getElementById("add-week");


// --- Functions ---

function createWeekRow(week) {

    const tr =
        document.createElement("tr");

    tr.innerHTML = `

        <td>${week.title}</td>

        <td>${week.start_date}</td>

        <td>${week.description}</td>

        <td>

            <button
                class="edit-btn"
                data-id="${week.id}"
            >
                Edit
            </button>

            <button
                class="delete-btn"
                data-id="${week.id}"
            >
                Delete
            </button>

        </td>
    `;

    return tr;
}


function renderTable() {

    weeksTbody.innerHTML = "";

    weeks.forEach((week) => {

        const row =
            createWeekRow(week);

        weeksTbody.appendChild(row);
    });
}


async function handleAddWeek(event) {

    event.preventDefault();

    const title =
        document.getElementById("week-title")
        .value;

    const start_date =
        document.getElementById("week-start-date")
        .value;

    const description =
        document.getElementById("week-description")
        .value;

    const links =
        document.getElementById("week-links")
        .value
        .split("\n")
        .filter(link => link.trim() !== "");

    const editId =
        submitButton.dataset.editId;

    const fields = {
        title,
        start_date,
        description,
        links
    };

    // UPDATE MODE

    if (editId) {

        await handleUpdateWeek(
            parseInt(editId),
            fields
        );

        return;
    }

    // ADD MODE

    try {

        const response =
            await fetch("./api/index.php", {

                method: "POST",

                headers: {
                    "Content-Type":
                        "application/json"
                },

                body: JSON.stringify(fields)
            });

        const result =
            await response.json();

        if (result.success) {

            weeks.push({

                id: result.id,

                ...fields
            });

            renderTable();

            weekForm.reset();
        }

    } catch (error) {

        console.error(
            "Error adding week:",
            error
        );
    }
}


async function handleUpdateWeek(id, fields) {

    try {

        const response =
            await fetch("./api/index.php", {

                method: "PUT",

                headers: {
                    "Content-Type":
                        "application/json"
                },

                body: JSON.stringify({
                    id,
                    ...fields
                })
            });

        const result =
            await response.json();

        if (result.success) {

            weeks = weeks.map((week) => {

                if (week.id === id) {

                    return {
                        id,
                        ...fields
                    };
                }

                return week;
            });

            renderTable();

            weekForm.reset();

            submitButton.textContent =
                "Add Week";

            delete submitButton.dataset.editId;
        }

    } catch (error) {

        console.error(
            "Error updating week:",
            error
        );
    }
}


async function handleTableClick(event) {

    // DELETE

    if (
        event.target.classList.contains(
            "delete-btn"
        )
    ) {

        const id =
            parseInt(
                event.target.dataset.id
            );

        try {

            const response =
                await fetch(
                    `./api/index.php?id=${id}`,
                    {
                        method: "DELETE"
                    }
                );

            const result =
                await response.json();

            if (result.success) {

                weeks = weeks.filter(
                    (week) => week.id !== id
                );

                renderTable();
            }

        } catch (error) {

            console.error(
                "Error deleting week:",
                error
            );
        }
    }


    // EDIT

    if (
        event.target.classList.contains(
            "edit-btn"
        )
    ) {

        const id =
            parseInt(
                event.target.dataset.id
            );

        const week =
            weeks.find(
                (week) => week.id === id
            );

        if (!week) {
            return;
        }

        document.getElementById(
            "week-title"
        ).value = week.title;

        document.getElementById(
            "week-start-date"
        ).value = week.start_date;

        document.getElementById(
            "week-description"
        ).value = week.description;

        document.getElementById(
            "week-links"
        ).value =
            week.links.join("\n");

        submitButton.textContent =
            "Update Week";

        submitButton.dataset.editId =
            id;
    }
}


async function loadAndInitialize() {

    try {

        const response =
            await fetch("./api/index.php");

        const result =
            await response.json();

        weeks = result.data || [];

        renderTable();

        weekForm.addEventListener(
            "submit",
            handleAddWeek
        );

        weeksTbody.addEventListener(
            "click",
            handleTableClick
        );

    } catch (error) {

        console.error(
            "Error loading weeks:",
            error
        );
    }
}


// --- Initial Page Load ---

loadAndInitialize();
