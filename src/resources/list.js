// Element Selection
const resourceListSection = document.getElementById('resource-list-section');

/**
 * Create resource article
 */
function createResourceArticle(resource) {
    // Create article element
    const article = document.createElement('article');
    
    // Add heading for title (h3)
    const title = document.createElement('h3');
    title.textContent = resource.title;
    article.appendChild(title);
    
    // Add paragraph for description
    const description = document.createElement('p');
    description.textContent = resource.description || 'No description available.';
    article.appendChild(description);
    
    // Add anchor tag for detail link
    const detailLink = document.createElement('a');
    detailLink.href = `details.html?id=${resource.id}`;
    detailLink.textContent = 'View Resource & Discussion';
    detailLink.className = 'button';
    article.appendChild(detailLink);
    
    return article;
}

/**
 * Load and display all resources
 */
async function loadResources() {
    try {
        // Fetch from API
        const response = await fetch('./api/index.php');
        const result = await response.json();
        
        if (result.success && result.data) {
            // Clear section
            resourceListSection.innerHTML = '';
            
            // Check if no resources
            if (result.data.length === 0) {
                resourceListSection.innerHTML = '<p>No resources available yet. Check back later!</p>';
                return;
            }
            
            // Loop and append each resource
            result.data.forEach(resource => {
                resourceListSection.appendChild(createResourceArticle(resource));
            });
        } else {
            resourceListSection.innerHTML = '<p>Failed to load resources. Please try again later.</p>';
        }
    } catch (error) {
        console.error('Error:', error);
        resourceListSection.innerHTML = '<p>An error occurred while loading resources.</p>';
    }
}

// Load resources
loadResources();
