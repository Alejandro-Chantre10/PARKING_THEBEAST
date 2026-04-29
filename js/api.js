/**
 * API Client - Parking The Beasts
 * Handles all API calls to the PHP backend
 */

// Detect if we're in a subdirectory (views) or root
function getApiBaseUrl() {
    const path = window.location.pathname;
    
    // Check if we're inside /views/ folder
    if (path.includes('/views/')) {
        return '../api';  // Go up one level from views to api
    }
    
    // We're in the root of the project
    return './api';  // api folder is in the same directory
}

const API_BASE_URL = getApiBaseUrl();

// Generic API request function
async function apiRequest(endpoint, method = 'GET', data = null) {
    const config = {
        method,
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'include' // Include cookies for session
    };

    if (data && method !== 'GET') {
        config.body = JSON.stringify(data);
    }

    try {
        const url = `${API_BASE_URL}${endpoint}`;
        const response = await fetch(url, config);
        const text = await response.text();
        
        // Try to parse as JSON
        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('Response is not JSON:', text.substring(0, 200));
            throw new Error('El servidor devolvió una respuesta inválida. Verifica que PHP esté funcionando correctamente.');
        }
        
        if (!response.ok) {
            throw new Error(result.message || 'Error en la solicitud');
        }
        
        return result;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

// ==========================================
// AUTH API
// ==========================================

const AuthAPI = {
    async register(fullName, email, password, phone = '') {
        return apiRequest('/auth.php?action=register', 'POST', {
            full_name: fullName,
            email,
            password,
            phone
        });
    },

    async login(email, password) {
        return apiRequest('/auth.php?action=login', 'POST', { email, password });
    },

    async logout() {
        return apiRequest('/auth.php?action=logout', 'POST');
    },

    async checkAuth() {
        return apiRequest('/auth.php?action=check', 'GET');
    }
};

// ==========================================
// USERS API
// ==========================================

const UsersAPI = {
    async getProfile() {
        return apiRequest('/users.php?action=profile', 'GET');
    },

    async updateProfile(fullName, phone) {
        return apiRequest('/users.php?action=profile', 'PUT', { full_name: fullName, phone });
    },

    async updatePassword(currentPassword, newPassword) {
        return apiRequest('/users.php?action=password', 'PUT', {
            current_password: currentPassword,
            new_password: newPassword
        });
    },

    async deleteAccount() {
        return apiRequest('/users.php?action=account', 'DELETE');
    },

    async getAllUsers(limit = 50, offset = 0) {
        return apiRequest(`/users.php?action=list&limit=${limit}&offset=${offset}`, 'GET');
    }
};

// ==========================================
// RESERVATIONS API
// ==========================================

const ReservationsAPI = {
    async create(vehicleTypeId, vehiclePlate, startAt, endAt, vehicleDescription = '', notes = '') {
        return apiRequest('/reservations.php?action=create', 'POST', {
            vehicle_type_id: vehicleTypeId,
            vehicle_plate: vehiclePlate,
            start_at: startAt,
            end_at: endAt,
            vehicle_description: vehicleDescription,
            notes
        });
    },

    async getList(status = null) {
        let url = '/reservations.php?action=list';
        if (status) url += `&status=${status}`;
        return apiRequest(url, 'GET');
    },

    async getById(id) {
        return apiRequest(`/reservations.php?action=detail&id=${id}`, 'GET');
    },

    async update(id, data) {
        return apiRequest(`/reservations.php?action=update&id=${id}`, 'PUT', data);
    },

    async cancel(id) {
        return apiRequest(`/reservations.php?action=cancel&id=${id}`, 'DELETE');
    },

    async checkAvailability(vehicleTypeId, startAt, endAt, facilityId = 1) {
        return apiRequest(
            `/reservations.php?action=check-availability&facility_id=${facilityId}&vehicle_type_id=${vehicleTypeId}&start_at=${encodeURIComponent(startAt)}&end_at=${encodeURIComponent(endAt)}`,
            'GET'
        );
    },

    async calculatePrice(vehicleTypeId, startAt, endAt, facilityId = 1) {
        return apiRequest(
            `/reservations.php?action=calculate-price&facility_id=${facilityId}&vehicle_type_id=${vehicleTypeId}&start_at=${encodeURIComponent(startAt)}&end_at=${encodeURIComponent(endAt)}`,
            'GET'
        );
    },

    async getAll(limit = 50, offset = 0, filters = {}) {
        let url = `/reservations.php?action=all&limit=${limit}&offset=${offset}`;
        if (filters.status) url += `&status=${filters.status}`;
        if (filters.facilityId) url += `&facility_id=${filters.facilityId}`;
        if (filters.dateFrom) url += `&date_from=${filters.dateFrom}`;
        if (filters.dateTo) url += `&date_to=${filters.dateTo}`;
        return apiRequest(url, 'GET');
    }
};

// ==========================================
// PAYMENTS API
// ==========================================

const PaymentsAPI = {
    async create(reservationId, method) {
        return apiRequest('/payments.php?action=create', 'POST', {
            reservation_id: reservationId,
            method
        });
    },

    async process(paymentId) {
        return apiRequest(`/payments.php?action=process&id=${paymentId}`, 'POST');
    },

    async getList() {
        return apiRequest('/payments.php?action=list', 'GET');
    },

    async getById(id) {
        return apiRequest(`/payments.php?action=detail&id=${id}`, 'GET');
    },

    async getByReservation(reservationId) {
        return apiRequest(`/payments.php?action=by-reservation&reservation_id=${reservationId}`, 'GET');
    }
};

// ==========================================
// RATES API
// ==========================================

const RatesAPI = {
    async getList() {
        return apiRequest('/rates.php?action=list', 'GET');
    },

    async getByFacility(facilityId) {
        return apiRequest(`/rates.php?action=by-facility&facility_id=${facilityId}`, 'GET');
    },

    async getById(id) {
        return apiRequest(`/rates.php?action=detail&id=${id}`, 'GET');
    }
};

// ==========================================
// VEHICLE TYPES API
// ==========================================

const VehicleTypesAPI = {
    async getList() {
        return apiRequest('/vehicle-types.php?action=list', 'GET');
    },

    async getById(id) {
        return apiRequest(`/vehicle-types.php?action=detail&id=${id}`, 'GET');
    }
};

// ==========================================
// FACILITIES API
// ==========================================

const FacilitiesAPI = {
    async getList() {
        return apiRequest('/facilities.php?action=list', 'GET');
    },

    async getById(id) {
        return apiRequest(`/facilities.php?action=detail&id=${id}`, 'GET');
    },

    async getCapacity(id) {
        return apiRequest(`/facilities.php?action=capacity&id=${id}`, 'GET');
    },

    async getOccupancy(id) {
        return apiRequest(`/facilities.php?action=occupancy&id=${id}`, 'GET');
    }
};

// ==========================================
// PQR API
// ==========================================

const PQRAPI = {
    async create(type, description) {
        return apiRequest('/pqr.php?action=create', 'POST', { type, description });
    },

    async getList() {
        return apiRequest('/pqr.php?action=list', 'GET');
    },

    async getById(id) {
        return apiRequest(`/pqr.php?action=detail&id=${id}`, 'GET');
    },

    async updateStatus(id, status, response = null) {
        return apiRequest(`/pqr.php?action=update&id=${id}`, 'PUT', { status, response });
    },

    async getAll(limit = 50, offset = 0) {
        return apiRequest(`/pqr.php?action=all&limit=${limit}&offset=${offset}`, 'GET');
    }
};

// ==========================================
// UTILITY FUNCTIONS
// ==========================================

// Store user data in localStorage
function storeUserSession(user) {
    localStorage.setItem('user', JSON.stringify(user));
}

// Get user data from localStorage
function getUserSession() {
    const user = localStorage.getItem('user');
    return user ? JSON.parse(user) : null;
}

// Alias for getUserSession
function getCurrentUser() {
    return getUserSession();
}

// Clear user session
function clearUserSession() {
    localStorage.removeItem('user');
    localStorage.removeItem('token');
}

// Check if user is logged in
function isLoggedIn() {
    return getUserSession() !== null;
}

// Redirect to login if not authenticated
function requireLogin() {
    if (!isLoggedIn()) {
        const path = window.location.pathname;
        // Check if we're inside /views/ folder
        if (path.includes('/views/')) {
            window.location.href = '../inicioSesion.html';
        } else {
            window.location.href = './inicioSesion.html';
        }
        return false;
    }
    return true;
}

// Format currency (COP)
function formatCurrency(amount) {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    }).format(amount);
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-CO', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

// Format time
function formatTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleTimeString('es-CO', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Format datetime
function formatDateTime(dateString) {
    return `${formatDate(dateString)} ${formatTime(dateString)}`;
}

// Show notification/alert
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()">&times;</button>
    `;
    
    // Add styles if not already added
    if (!document.getElementById('notification-styles')) {
        const styles = document.createElement('style');
        styles.id = 'notification-styles';
        styles.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                color: white;
                display: flex;
                align-items: center;
                gap: 10px;
                z-index: 9999;
                animation: slideIn 0.3s ease;
            }
            .notification-success { background-color: #4CAF50; }
            .notification-error { background-color: #f44336; }
            .notification-info { background-color: #2196F3; }
            .notification-warning { background-color: #ff9800; }
            .notification button {
                background: none;
                border: none;
                color: white;
                font-size: 20px;
                cursor: pointer;
            }
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(styles);
    }
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => notification.remove(), 5000);
}

// Export for use in modules (if needed)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        AuthAPI,
        UsersAPI,
        ReservationsAPI,
        PaymentsAPI,
        RatesAPI,
        VehicleTypesAPI,
        FacilitiesAPI,
        PQRAPI
    };
}
