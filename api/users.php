<?php
require_once __DIR__ . '/../config.php';

if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
} else {
    header("Access-Control-Allow-Origin: *");
}
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: content-type, Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');

// Handle preflight (OPTIONS) requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Handle email check via POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['email'])) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => 'Email is required']);
        exit();
    }

    $email = trim($input['email']);

    if (empty($email)) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => 'Email cannot be empty']);
        exit();
    }

    // Check if email exists in the database
    $exists = false;
    if ($useSupabase) {
        $result = supabaseRequest('tbl_users', 'GET', null, ['email' => 'eq.' . $email], 'user_id');
        $exists = !empty($result) && !isset($result['error']);
    } else {
        $stmt = $mysqli->prepare("SELECT user_id FROM tbl_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        $mysqli->close();
    }

    if ($exists) {
        // Email exists
        http_response_code(200);
        echo json_encode([
            'status' => 200, 
            'exists' => true, 
            'message' => 'Email is already registered'
        ]);
    } else {
        // Email doesn't exist
        http_response_code(200);
        echo json_encode([
            'status' => 200, 
            'exists' => false, 
            'message' => 'Email is available'
        ]);
    }
    exit();
}

// Handle GET request - return list of users or check email via query parameter
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if email query parameter is provided for email validation
    if (isset($_GET['email'])) {
        $email = trim($_GET['email']);
        
        if (empty($email)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'Email cannot be empty']);
            exit();
        }

        // Check if email exists in the database
        $exists = false;
        if ($useSupabase) {
            $result = supabaseRequest('tbl_users', 'GET', null, ['email' => 'eq.' . $email], 'user_id');
            $exists = !empty($result) && !isset($result['error']);
        } else {
            $stmt = $mysqli->prepare("SELECT user_id FROM tbl_users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $exists = $result->num_rows > 0;
            $stmt->close();
        }

        if ($exists) {
            // Email exists
            echo json_encode([
                'status' => 200, 
                'exists' => true, 
                'message' => 'Email is already registered'
            ]);
        } else {
            // Email doesn't exist
            echo json_encode([
                'status' => 200, 
                'exists' => false, 
                'message' => 'Email is available'
            ]);
        }
        
        if (!$useSupabase) {
            $mysqli->close();
        }
        exit();
    }

    // Default GET behavior - return list of users
    $users = [];
    if ($useSupabase) {
        $result = supabaseRequest('tbl_users', 'GET');
        if (!isset($result['error'])) {
            $users = $result;
        }
    } else {
        $result = $mysqli->query("SELECT * FROM tbl_users");
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        $mysqli->close();
    }
    echo json_encode($users);
    exit();
}

// Method not allowed
http_response_code(405);
echo json_encode(['status' => 405, 'message' => 'Method Not Allowed']);
?>
