// Global Data Store
let resources = [];
let editMode = false;
let editId = null;

// Element Selections
const resourceForm = document.getElementById('resource-form');
const resourcesTbody = document.getElementById('resources-tbody');
const formHeading = document.getElementById('form-heading');
const addResourceBtn = document.getElementById('add-resource');

/**
 * Create a table row for a resource
 */
function createResourceRow(resource) {
    // Create tr element
    const tr = document.createElement('tr');
    
    // Add td for title
    const titleTd = document.createElement('td');
    titleTd.textContent = resource.title;
    tr.appendChild(titleTd);
    
    // Add td for description
    const descTd = document.createElement('td');
    descTd.textContent = resource.description || '';
    tr.appendChild(descTd);
    
    // Add td for link
    const linkTd = document.createElement('td');
    const link = document.createElement('a');
    link.href = resource.link;
    link.textContent = resource.link.length > 40 ? resource.link.substring(0, 40) + '...' : resource.link;
    link.target = '_blank';
    linkTd.appendChild(link);
    tr.appendChild(linkTd);
    
    // Add td for actions with buttons
    const actionsTd = document.createElement('td');
    
    // Edit button
    const editBtn = document.createElement('button');
    editBtn.textContent = 'Edit';
    editBtn.className = 'edit-btn';
    editBtn.setAttribute('data-id', resource.id);
    editBtn.style.marginRight = '8px';
    
    // Delete button
    const deleteBtn = document.createElement('button');
    deleteBtn.textContent = 'Delete';
    deleteBtn.className = 'delete-btn';
    deleteBtn.setAttribute('data-id', resource.id);
    deleteBtn.style.backgroundColor = '#dc3545';
    
    actionsTd.appendChild(editBtn);
    actionsTd.appendChild(deleteBtn);
    tr.appendChild(actionsTd);
    
    return tr;
}

/**
 * Render the entire table
 */
function renderTable() {
    // Clear the table body
    resourcesTbody.innerHTML = '';
    
    // Loop through resources and append rows
    resources.forEach(resource => {
        resourcesTbody.appendChild(createResourceRow(resource));
    });
}

/**
 * Reset form to "Add" mode
 */
function resetForm() {
    document.getElementById('resource-title').value = '';
    document.getElementById('resource-description').value = '';
    document.getElementById('resource-link').value = '';
    editMode = false;
    editId = null;
    if (formHeading) formHeading.textContent = 'Add a New Resource';
    if (addResourceBtn) addResourceBtn.textContent = 'Add Resource';
}

/**
 * Handle Add/Update Resource
 */
async function handleAddResource(event) {
    // Prevent default submission
    event.preventDefault();
    
    // Get values from inputs
    const title = document.getElementById('resource-title').value.trim();
    const description = document.getElementById('resource-description').value.trim();
    const link = document.getElementById('resource-link').value.trim();
    
    // Validate
    if (!title || !link) {
        alert('Title and link are required');
        return;
    }
    
    // Determine if add or update
    let url = './api/index.php';
    let method = 'POST';
    let body = { title, description, link };
    
    if (editMode && editId) {
        method = 'PUT';
        body = { id: editId, title, description, link };
    }
    
    try {
        // Send request
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Reload resources
            await loadAndInitialize();
            // Reset form
            resetForm();
        } else {
            alert(result.message || 'Operation failed');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred');
    }
}

/**
 * Handle Edit/Delete buttons
 */
async function handleTableClick(event) {
    const target = event.target;
    
    // Handle Delete
    if (target.classList.contains('delete-btn')) {
        const id = target.getAttribute('data-id');
        if (confirm('Are you sure you want to delete this resource?')) {
            try {
                const response = await fetch(`./api/index.php?id=${id}`, {
                    method: 'DELETE'
                });
                const result = await response.json();
                if (result.success) {
                    await loadAndInitialize();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to delete resource');
            }
        }
    }
    
    // Handle Edit
    if (target.classList.contains('edit-btn')) {
        const id = parseInt(target.getAttribute('data-id'));
        const resource = resources.find(r => r.id === id);
        
        if (resource) {
            // Populate form with resource values
            document.getElementById('resource-title').value = resource.title;
            document.getElementById('resource-description').value = resource.description || '';
            document.getElementById('resource-link').value = resource.link;
            
            // Set edit mode
            editMode = true;
            editId = id;
            
            // Change button text
            if (formHeading) formHeading.textContent = 'Edit Resource';
            if (addResourceBtn) addResourceBtn.textContent = 'Update Resource';

            
            // Scroll to form
            const form = document.getElementById('resource-form');
            if (form && typeof form.scrollIntoView === 'function') {
            form.scrollIntoView({ behavior: 'smooth' });
}
        }
    }
}

/**
 * Load resources and initialize
 */
async function loadAndInitialize() {
    try {
        // Fetch all resources
        const response = await fetch('./api/index.php');
        const result = await response.json();
        
        if (result.success && result.data) {
            // Store in global variable
            resources = result.data;
            // Render table
            renderTable();
        }
    } catch (error) {
        console.error('Error loading resources:', error);
    }
}

// Add event listeners
resourceForm.addEventListener('submit', handleAddResource);
resourcesTbody.addEventListener('click', handleTableClick);

// Initialize page
loadAndInitialize();
