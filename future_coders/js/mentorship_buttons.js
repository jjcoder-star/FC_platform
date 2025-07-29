document.addEventListener('DOMContentLoaded', function() {
    console.log('Document loaded, initializing mentorship buttons...');
    
    // Initialize all action buttons
    const actionButtons = document.querySelectorAll('.action-button');
    console.log(`Found ${actionButtons.length} action buttons to initialize`);
    
    actionButtons.forEach(container => {
        try {
            updateActionButton(container);
        } catch (error) {
            console.error('Error initializing button:', error);
        }
    });

    // Handle form submissions
    document.addEventListener('submit', async function(e) {
        if (e.target.classList.contains('request-form')) {
            e.preventDefault();
            console.log('Request form submitted');
            
            try {
                await handleRequestSubmission(e.target);
            } catch (error) {
                console.error('Form submission error:', error);
                showAlert('Failed to send request. Please try again.', 'error');
            }
        }
    });

    // Handle enter button clicks
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-enter')) {
            console.log('Enter button clicked');
            try {
                const programName = e.target.closest('.action-button').dataset.program;
                enterProgram(programName);
            } catch (error) {
                console.error('Enter button error:', error);
            }
        }
    });
});

function updateActionButton(container) {
    if (!container) {
        console.error('Container element not found');
        return;
    }

    const status = container.dataset.status;
    const programName = container.dataset.program;
    const mentorId = container.dataset.mentorId;

    console.log(`Updating button for program "${programName}" with status "${status}"`);

    if (!status || !programName || !mentorId) {
        console.error('Missing required data attributes:', {status, programName, mentorId});
        return;
    }

    container.innerHTML = '';
    
    if (status === 'active') {
        container.innerHTML = `
            <button class="btn btn-enter">
                <i class="fas fa-door-open"></i> Enter
            </button>
        `;
    } 
    else if (status === 'pending') {
        container.innerHTML = `
            <button class="btn btn-pending" disabled>
                <i class="fas fa-clock"></i> Pending
            </button>
        `;
    }
    else {
        container.innerHTML = `
            <form class="request-form" action="send_request.php" method="POST">
                <input type="hidden" name="mentor_id" value="${mentorId}">
                <input type="hidden" name="program_name" value="${encodeURIComponent(programName)}">
                <button type="submit" class="btn btn-request">
                    <i class="fas fa-user-plus"></i> Request
                </button>
            </form>
        `;
    }
}

async function handleRequestSubmission(form) {
    console.log('Handling request submission...');
    
    const buttonContainer = form.closest('.action-button');
    if (!buttonContainer) {
        throw new Error('Could not find button container');
    }

    const formData = new FormData(form);
    const programName = formData.get('program_name');
    
    console.log('Submitting request for program:', programName);
    
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        });

        console.log('Received response, status:', response.status);
        
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Server responded with ${response.status}: ${errorText}`);
        }

        const data = await response.json();
        console.log('Response data:', data);
        
        if (!data.success) {
            throw new Error(data.message || 'Request failed');
        }

        // Update UI on success
        buttonContainer.dataset.status = 'pending';
        updateActionButton(buttonContainer);
        showAlert('Request sent successfully!', 'success');
        
    } catch (error) {
        console.error('Request failed:', error);
        showAlert(error.message || 'Failed to send request', 'error');
        throw error; // Re-throw for outer catch
    }
}

function enterProgram(programName) {
    console.log(`Attempting to enter program: ${programName}`);
    // window.location.href = `program.php?name=${encodeURIComponent(programName)}`;
}

function showAlert(message, type) {
    console.log(`Showing alert: ${type} - ${message}`);
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const programsSection = document.querySelector('.mentor-programs');
    if (!programsSection) {
        console.error('Could not find programs section for alert');
        return;
    }
    
    programsSection.insertBefore(alertDiv, programsSection.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}