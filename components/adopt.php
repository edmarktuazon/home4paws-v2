<?php
// Database configuration
$host = 'localhost';
$dbname = 'home4paws_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    $adoptables = [];
}

// Fetch adoptable animals
try {
    $stmt = $pdo->query("SELECT * FROM adoptable_animals ORDER BY created_at DESC");
    $adoptables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Query failed for adoptable animals: " . $e->getMessage());
    $adoptables = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adoptable Animals</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
    .home-green {
        color: #10B981;
    }
    </style>
</head>

<body class="bg-gray-50">
    <section id="animals" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="text-4xl font-bold text-center text-home-green mb-12">Our Animals</h2>
            <div id="animals-grid" class="grid md:grid-cols-3 gap-8">
                <?php if (empty($adoptables)): ?>
                <p class="text-center text-gray-500 py-8" id="no-adopt-images">No adoptable animals yet. Check back
                    soon!</p>
                <?php else: ?>
                <?php foreach ($adoptables as $animal): ?>
                <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 hover:shadow-xl transition-all duration-300 overflow-hidden"
                    data-image-id="<?php echo $animal['id']; ?>">
                    <img src="data:image/jpeg;base64,<?php echo htmlspecialchars($animal['image_data']); ?>"
                        alt="Adoptable animal" class="w-full h-48 object-cover rounded-lg mb-4"
                        onerror="this.src='https://placehold.co/300x200?text=Image+Not+Found';this.onerror=null;" />
                    <div class="flex justify-center">
                        <a href="https://www.facebook.com/messages/t/315034048360145" target="_blank"
                            class="block w-full bg-home-green text-white text-center py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">Support
                            Me</a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="text-center mt-8">
                <p id="all-shown" class="text-gray-600 hidden">All animals are shown! Join our adoption drives to meet
                    more.</p>
            </div>
        </div>
    </section>
    <script>
    // Poll for new and deleted adoptable animals every 3 seconds
    let lastAdoptId = <?php echo !empty($adoptables) ? max(array_column($adoptables, 'id')) : 0; ?>;
    let currentAdoptIds = [<?php echo !empty($adoptables) ? implode(',', array_column($adoptables, 'id')) : ''; ?>];

    function pollImages() {
        console.log('Polling for adoptable animals, last adopt ID:', lastAdoptId);
        $.ajax({
            url: 'fetch_images.php',
            type: 'GET',
            data: {
                last_adopt_id: lastAdoptId
            },
            dataType: 'json',
            success: function(response) {
                console.log('Fetch images response:', response);
                // Handle new adoptable animals
                if (response.adopt_images && response.adopt_images.length > 0) {
                    const gallery = $('#animals-grid');
                    const noImages = $('#no-adopt-images');
                    if (noImages.length) {
                        noImages.remove();
                    }
                    response.adopt_images.forEach(animal => {
                        const newCard = `
                            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 hover:shadow-xl transition-all duration-300 overflow-hidden" data-image-id="${animal.id}">
                                <img src="data:image/jpeg;base64,${animal.image_data}" alt="Adoptable animal" class="w-full h-48 object-cover rounded-lg mb-4" onerror="this.src='https://placehold.co/300x200?text=Image+Not+Found';this.onerror=null;" />
                                <div class="flex justify-center">
                                    <a href="https://www.facebook.com/messages/t/315034048360145" target="_blank" class="block w-full bg-home-green text-white text-center py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">Support Me</a>
                                </div>
                            </div>`;
                        gallery.prepend(newCard);
                        currentAdoptIds.unshift(parseInt(animal.id));
                    });
                    lastAdoptId = Math.max(lastAdoptId, ...response.adopt_images.map(img => parseInt(img
                        .id)));
                    console.log('Updated lastAdoptId:', lastAdoptId);
                }
                // Handle deleted adoptable animals
                if (response.adopt_ids) {
                    const newAdoptIds = response.adopt_ids.map(id => parseInt(id));
                    const deletedAdoptIds = currentAdoptIds.filter(id => !newAdoptIds.includes(id));
                    deletedAdoptIds.forEach(id => {
                        $(`div[data-image-id="${id}"]`).remove();
                        console.log('Removed deleted adoptable animal, ID:', id);
                    });
                    currentAdoptIds = newAdoptIds;
                    if (currentAdoptIds.length === 0 && !$('#no-adopt-images').length) {
                        $('#animals-grid').append(
                            '<p class="text-center text-gray-500 py-8" id="no-adopt-images">No adoptable animals yet. Check back soon!</p>'
                        );
                    }
                }
                if (currentAdoptIds.length === animals.length) {
                    $('#all-shown').removeClass('hidden');
                } else {
                    $('#all-shown').addClass('hidden');
                }
            },
            error: function(xhr, status, error) {
                console.error('Polling error:', status, error, xhr.responseText);
            },
            complete: function() {
                setTimeout(pollImages, 3000); // Poll every 3 seconds
            }
        });
    }
    pollImages();
    </script>
</body>

</html>