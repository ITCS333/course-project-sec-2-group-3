// Global Data Store
let currentResourceId = null;
let currentComments = [];

// Element Selections
const resourceTitle = document.getElementById('resource-title');
const resourceDescription = document.getElementById('resource-description');
const resourceLink = document.getElementById('resource-link');
const commentList = document.getElementById('comment-list');
const commentForm = document.getElementById('comment-form');
const newComment = document.getElementById('new-comment');

/**
 * Get resource ID from URL
 */
function getResourceIdFromURL() {
    // Get query string and get 'id' parameter
    const params = new URLSearchParams(window.location.search);
    return params.get('id');
}

/**
 * Render resource details
 */
function renderResourceDetails(resource) {
    // Set title textContent
    resourceTitle.textContent = resource.title;
    
    // Set description textContent
    resourceDescription.textContent = resource.description || 'No description provided.';
    
    // Set link href attribute
    resourceLink.href = resource.link;
}

/**
 * Create comment article
 */
function createCommentArticle(comment) {
    // Create article element
    const article = document.createElement('article');
    
    // Add p for comment text
    const textP = document.createElement('p');
    textP.textContent = comment.text;
    article.appendChild(textP);
    
    // Add footer for author
    const footer = document.createElement('footer');
    const date = new Date(comment.created_at).toLocaleDateString();
    footer.textContent = `Posted by: ${comment.author} on ${date}`;
    article.appendChild(footer);
    
    return article;
}

/**
 * Render all comments
 */
function renderComments() {
    // Clear comment list
    commentList.innerHTML = '';
    
    // If no comments
    if (currentComments.length === 0) {
        commentList.innerHTML = '<p>No comments yet. Be the first to comment!</p>';
        return;
    }
    
    // Loop and append each comment
    currentComments.forEach(comment => {
        commentList.appendChild(createCommentArticle(comment));
    });
}

/**
 * Handle adding a new comment
 */
async function handleAddComment(event) {
    // Prevent default submission
    event.preventDefault();
    
    // Get comment text
    const commentText = newComment.value.trim();
    
    // Validate
    if (!commentText) {
        alert('Please enter a comment');
        return;
    }
    
    try {
        // Send POST request
        const response = await fetch('./api/index.php?action=comment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                resource_id: currentResourceId,
                author: 'Student',
                text: commentText
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Reload comments
            const commentsResponse = await fetch(`./api/index.php?resource_id=${currentResourceId}&action=comments`);
            const commentsResult = await commentsResponse.json();
            if (commentsResult.success) {
                currentComments = commentsResult.data;
                renderComments();
            }
            // Clear textarea
            newComment.value = '';
        } else {
            alert(result.message || 'Failed to post comment');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while posting your comment');
    }
}

/**
 * Initialize the page
 */
async function initializePage() {
    // Get resource ID from URL
    currentResourceId = getResourceIdFromURL();
    
    // If no ID, show error
    if (!currentResourceId) {
        resourceTitle.textContent = 'Resource not found';
        resourceDescription.textContent = 'No resource ID provided.';
        return;
    }
    
    try {
        // Fetch resource and comments in parallel
        const [resourceResponse, commentsResponse] = await Promise.all([
            fetch(`./api/index.php?id=${currentResourceId}`),
            fetch(`./api/index.php?resource_id=${currentResourceId}&action=comments`)
        ]);
        
        const resourceResult = await resourceResponse.json();
        const commentsResult = await commentsResponse.json();
        
        // Handle resource
        if (resourceResult.success && resourceResult.data) {
            renderResourceDetails(resourceResult.data);
        } else {
            resourceTitle.textContent = 'Resource not found';
            resourceDescription.textContent = 'The requested resource does not exist.';
        }
        
        // Handle comments
        if (commentsResult.success) {
            currentComments = commentsResult.data || [];
            renderComments();
        }
        
        // Add event listener to comment form
        commentForm.addEventListener('submit', handleAddComment);
        
    } catch (error) {
        console.error('Error:', error);
        resourceTitle.textContent = 'Error loading resource';
        resourceDescription.textContent = 'An error occurred while loading the resource.';
    }
}

// Initialize page
initializePage();
