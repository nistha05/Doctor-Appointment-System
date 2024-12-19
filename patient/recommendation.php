<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "edoc";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch specialties and doctors data
$specialty_query = "SELECT id, sname FROM specialties";
$doctor_query = "SELECT docid, docname, specialties FROM doctors";

$specialties = [];
$doctors = [];

if ($result = $conn->query($specialty_query)) {
    while ($row = $result->fetch_assoc()) {
        $specialties[$row['id']] = $row['sname'];
    }
    $result->close();
}

if ($result = $conn->query($doctor_query)) {
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
    $result->close();
}

// Function to create binary vectors for specialties
function create_vector($specialty_ids, $total_specialties) {
    $vector = array_fill(0, $total_specialties, 0);
    foreach ($specialty_ids as $id) {
        $vector[$id - 1] = 1; // Specialty IDs start from 1
    }
    return $vector;
}

// Cosine similarity function
function cosine_similarity($vector1, $vector2) {
    $dot_product = 0;
    $magnitude1 = 0;
    $magnitude2 = 0;

    for ($i = 0; $i < count($vector1); $i++) {
        $dot_product += $vector1[$i] * $vector2[$i];
        $magnitude1 += $vector1[$i] ** 2;
        $magnitude2 += $vector2[$i] ** 2;
    }

    if ($magnitude1 == 0 || $magnitude2 == 0) return 0;

    return $dot_product / (sqrt($magnitude1) * sqrt($magnitude2));
}

// Input: Selected specialty ID
$selected_specialty_id = 5; // Example: Cardiology

// Create vector for the selected specialty
$total_specialties = count($specialties);
$selected_vector = create_vector([$selected_specialty_id], $total_specialties);

// Compute similarity for each doctor
$recommendations = [];

foreach ($doctors as $doctor) {
    $doctor_specialty_ids = explode(',', $doctor['specialties']); // Assuming specialties are stored as comma-separated IDs
    $doctor_vector = create_vector($doctor_specialty_ids, $total_specialties);
    $similarity = cosine_similarity($selected_vector, $doctor_vector);

    if ($similarity > 0) { // Only consider doctors with non-zero similarity
        $recommendations[] = [
            'docid' => $doctor['docid'],
            'docname' => $doctor['docname'],
            'similarity' => $similarity,
        ];
    }
}

// Sort recommendations by similarity
usort($recommendations, function ($a, $b) {
    return $b['similarity'] <=> $a['similarity'];
});

// Display top recommendations

foreach ($recommendations as $recommendation) {
    echo "<p>Doctor ID: {$recommendation['docid']}, Name: {$recommendation['docname']}, Similarity: {$recommendation['similarity']}</p>";
}

$conn->close();
?>
