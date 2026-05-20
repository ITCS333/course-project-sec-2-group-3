// --- Element Selections ---

const weekListSection =
    document.getElementById("week-list-section");


// --- Functions ---

function createWeekArticle(week) {

    const article = document.createElement("article");

    const title = document.createElement("h2");
    title.textContent = week.title;

    const startDate = document.createElement("p");
    startDate.textContent =
        `Starts on: ${week.start_date}`;

    const description = document.createElement("p");
    description.textContent = week.description;

    const link = document.createElement("a");

    link.href = `details.html?id=${week.id}`;

    link.textContent =
        "View Details & Discussion";

    article.appendChild(title);

    article.appendChild(startDate);

    article.appendChild(description);

    article.appendChild(link);

    return article;
}


async function loadWeeks() {

    try {

        const response =
            await fetch("./api/index.php");

        const result = await response.json();

        weekListSection.innerHTML = "";

        result.data.forEach((week) => {

            const article =
                createWeekArticle(week);

            weekListSection.appendChild(article);
        });

    } catch (error) {

        console.error(
            "Error loading weeks:",
            error
        );

        weekListSection.innerHTML =
            "<p>Failed to load weeks.</p>";
    }
}


// --- Initial Page Load ---

loadWeeks();
