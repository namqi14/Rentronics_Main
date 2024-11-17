const axios = require('axios');
const crypto = require('crypto');
const readline = require('readline');

// Function to get access token
async function getAccessToken(clientId, clientSecret) {
    const url = 'https://openapi.tuyaus.com/v1.0/token?grant_type=1';
    const timestamp = String(Date.now());
    const stringToSign = clientId + timestamp;
    const sign = crypto.createHmac('sha256', clientSecret).update(stringToSign).digest('hex').toUpperCase();

    const headers = {
        'client_id': clientId,
        'sign': sign,
        't': timestamp,
        'sign_method': 'HMAC-SHA256',
        'Content-Type': 'application/json'
    };

    try {
        const response = await axios.get(url, { headers });
        console.log(`Request URL: ${url}`);
        console.log(`Request Headers:`, headers);
        console.log(`HTTP Status Code: ${response.status}`);
        console.log(`Response:`, response.data);

        return response.data.result?.access_token;
    } catch (error) {
        console.error('Failed to get access token:', error.response ? error.response.data : error.message);
    }
}

// Function to fetch devices
async function fetchDevices(accessToken) {
    const url = 'https://openapi.tuyaus.com/v1.0/devices';
    const headers = {
        'Authorization': `Bearer ${accessToken}`,
        'Content-Type': 'application/json'
    };

    try {
        const response = await axios.get(url, { headers });
        console.log(`Devices Request URL: ${url}`);
        console.log(`Devices Request Headers:`, headers);
        console.log(`Devices HTTP Status Code: ${response.status}`);
        console.log(`Devices Response:`, response.data);

        return response.data.result;
    } catch (error) {
        console.error('Failed to fetch devices:', error.response ? error.response.data : error.message);
    }
}

// Function to change password
async function changePassword(accessToken, deviceId, password) {
    const url = `https://openapi.tuyaus.com/v1.0/devices/${deviceId}/door-lock/passwords`;
    const headers = {
        'Authorization': `Bearer ${accessToken}`,
        'Content-Type': 'application/json'
    };
    const postData = {
        password: password
    };

    try {
        const response = await axios.post(url, postData, { headers });
        console.log(`Change Password Request URL: ${url}`);
        console.log(`Change Password Request Headers:`, headers);
        console.log(`Change Password Request Body:`, postData);
        console.log(`Change Password HTTP Status Code: ${response.status}`);
        console.log(`Change Password Response:`, response.data);

        return response.data;
    } catch (error) {
        console.error('Failed to change password:', error.response ? error.response.data : error.message);
    }
}

// Function to prompt user input
function promptUser(query) {
    const rl = readline.createInterface({
        input: process.stdin,
        output: process.stdout
    });

    return new Promise(resolve => rl.question(query, answer => {
        rl.close();
        resolve(answer);
    }));
}

const clientId = 'vw55nknx8twhrqu78wyd'; // Replace with your actual Client ID
const clientSecret = '555bb62672f840159d97dbed9a3c6e91'; // Replace with your actual Client Secret

(async () => {
    const accessToken = await getAccessToken(clientId, clientSecret);
    if (accessToken) {
        const devices = await fetchDevices(accessToken);
        if (devices && devices.length > 0) {
            console.log('Devices:');
            devices.forEach((device, index) => {
                console.log(`${index + 1}. ${device.name} (ID: ${device.id})`);
            });

            const deviceIndex = await promptUser('Enter the number of the device to change the password: ');
            const device = devices[deviceIndex - 1];

            if (device) {
                const password = await promptUser('Enter the new password: ');
                const result = await changePassword(accessToken, device.id, password);
                console.log('Change Password Result:', result);
            } else {
                console.log('Invalid device selection.');
            }
        } else {
            console.log('No devices found.');
        }
    } else {
        console.log('Failed to get access token');
    }
})();
