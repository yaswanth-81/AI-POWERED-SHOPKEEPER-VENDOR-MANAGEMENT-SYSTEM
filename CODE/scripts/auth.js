// Signup function
async function signupUser(formDataObj) {
    const formData = new FormData();
    formData.append('first_name', formDataObj.firstName);
    formData.append('last_name', formDataObj.lastName);
    formData.append('email', formDataObj.email);
    formData.append('password', formDataObj.password);
    formData.append('role', formDataObj.role);
    formData.append('address', formDataObj.address);
    formData.append('city', formDataObj.city);
    formData.append('state', formDataObj.state);
    formData.append('postal_code', formDataObj.postalCode);
    formData.append('country', formDataObj.country);
    formData.append('phone', formDataObj.phone);
    if (formDataObj.role === 'shopkeeper') {
        formData.append('shop_name', formDataObj.shopName || '');
        formData.append('shop_type', formDataObj.shopType || '');
    } else if (formDataObj.role === 'vendor') {
        formData.append('business_name', formDataObj.businessName || '');
        formData.append('vendor_type', formDataObj.vendorType || '');
    }

    const response = await fetch('signup.php', {
        method: 'POST',
        body: formData
    });
    const result = await response.text();
    return result.trim(); // Always return the backend response as a string
}

// Login function
async function loginUser(email, password, role) {
    const formData = new FormData();
    formData.append('email', email);
    formData.append('password', password);
    formData.append('role', role);

    const response = await fetch('login.php', {
        method: 'POST',
        body: formData
    });
    const result = (await response.text()).trim();
    
    // Handle different response types
    if (result === 'shopkeeper') {
        window.location.href = 'shopkeeper dashboard.html';
    } else if (result === 'vendor') {
        window.location.href = 'vendor_dashboard.php';
    } else if (result === 'admin') {
        window.location.href = 'admin dashboard.html';
    } else if (result === 'invalid') {
        throw new Error('Invalid password');
    } else if (result === 'notfound') {
        throw new Error('User not found with this email and role');
    } else if (result === 'missing') {
        throw new Error('Please fill in all required fields');
    } else if (result.startsWith('error:')) {
        // Handle specific error messages from the backend
        const errorMessage = result.substring(6); // Remove 'error:' prefix
        throw new Error(errorMessage);
    } else {
        throw new Error('Login failed: ' + result);
    }
}