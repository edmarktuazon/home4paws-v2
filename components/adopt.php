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

// Fetch adoptable animals (initial load limited to 10 for performance)
try {
    $stmt = $pdo->query("SELECT * FROM adoptable_animals ORDER BY created_at DESC LIMIT 10");
    $adoptables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Query failed for adoptable animals: " . $e->getMessage());
    $adoptables = [];
}
?>

<div id="debug-message"></div>
    <section id="adopt" class="py-20 lg:py-0 min-h-[70dvh] grid place-content-center">
        <div class="max-w-7xl mx-auto px-6">
           <h2 class="text-4xl md:text-5xl font-bold text-[#718a47] mb-22">
                Adoptable Animals
            </h2>
            <div id="adopt-gallery" class="grid md:grid-cols-3 gap-8">
                <?php if (empty($adoptables)): ?>
                <p class="text-center text-gray-500 py-8" id="no-adopt-images">No adoptable animals yet. Check back soon!</p>
                <?php else: ?>
                <?php foreach ($adoptables as $animal): ?>
                <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 hover:shadow-xl transition-shadow" data-image-id="<?php echo $animal['id']; ?>">
                    <img data-src="data:image/jpeg;base64,<?php echo htmlspecialchars($animal['image_data']); ?>" alt="Adoptable animal" class="w-full h-48 object-cover rounded-lg mb-4 lazy-img" onerror="this.src='https://placehold.co/300x200?text=Image+Not+Found';this.onerror=null;" />
                    <div class="flex justify-between items-center">
                        <a href="https://www.facebook.com/messages/t/315034048360145" target="_blank" class="bg-[#718a47] text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">Support Me</a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="text-center mt-8">
                <button id="load-more" class="bg-home-green text-white px-6 py-2 rounded-lg hover:bg-green-700 transition" style="display: none;">Load More</button>
            </div>
        </div>
    </section>
    <script>
    // Lazy load images
    function lazyLoadImages() {
        const images = document.querySelectorAll('.lazy-img:not(.loaded)');
        images.forEach(img => {
            if (img.getBoundingClientRect().top <= window.innerHeight + 100) {
                img.src = img.dataset.src;
                img.classList.add('loaded');
                img.removeAttribute('data-src');
            }
        });
    }

    window.addEventListener('load', lazyLoadImages);
    window.addEventListener('scroll', lazyLoadImages);
    window.addEventListener('resize', lazyLoadImages);

    // Initialize polling variables
    let lastAdoptId = <?php echo !empty($adoptables) ? max(array_column($adoptables, 'id')) : 0; ?>;
    let currentAdoptIds = [<?php echo !empty($adoptables) ? implode(',', array_column($adoptables, 'id')) : ''; ?>];
    let offset = <?php echo count($adoptables); ?>;
    const limit = 10;

    function pollImages() {
        console.log('Polling for adoptable animals, last ID:', lastAdoptId, 'current IDs:', currentAdoptIds);
        $.ajax({
            url: 'fetch_images.php',
            type: 'GET',
            data: { last_adopt_id: lastAdoptId },
            dataType: 'json',
            success: function(response) {
                console.log('Fetch images response:', response);
                const debugMessage = $('#debug-message');
                debugMessage.hide();

                // Validate response
                if (!response || typeof response !== 'object') {
                    console.error('Invalid response format:', response);
                    debugMessage.text('Invalid server response. Check console.').show();
                    setTimeout(() => debugMessage.fadeOut(), 5000);
                    return;
                }

                // Handle new adoptable animals
                if (response.adopt_images && Array.isArray(response.adopt_images) && response.adopt_images.length > 0) {
                    const gallery = $('#adopt-gallery');
                    const noImages = $('#no-adopt-images');
                    if (noImages.length) {
                        noImages.remove();
                        console.log('Removed no-adopt-images placeholder');
                    }
                    response.adopt_images.forEach(animal => {
                        const animalId = parseInt(animal.id);
                        if (!currentAdoptIds.includes(animalId) && animal.image_data) {
                            const newCard = `
                                <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 hover:shadow-xl transition-shadow" data-image-id="${animal.id}">
                                    <img data-src="data:image/jpeg;base64,${animal.image_data}" alt="Adoptable animal" class="w-full h-48 object-cover rounded-lg mb-4 lazy-img" onerror="this.src='https://placehold.co/300x200?text=Image+Not+Found';this.onerror=null;" />
                                    <div class="flex justify-between items-center">
                                        <a href="https://www.facebook.com/messages/t/315034048360145" target="_blank" class="bg-home-green text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">Support Me</a>
                                    </div>
                                </div>`;
                            gallery.prepend(newCard);
                            currentAdoptIds.unshift(animalId);
                            console.log('Added new adoptable animal, ID:', animal.id);
                        } else if (!animal.image_data) {
                            console.warn('Skipping animal with missing image_data, ID:', animal.id);
                        }
                    });
                    lastAdoptId = Math.max(lastAdoptId, ...response.adopt_images.map(img => parseInt(img.id)));
                    console.log('Updated lastAdoptId:', lastAdoptId);
                    lazyLoadImages();
                } else {
                    console.log('No new adoptable images in response');
                }

                // Handle deleted adoptable animals
                if (response.adopt_ids && Array.isArray(response.adopt_ids)) {
                    const newAdoptIds = response.adopt_ids.map(id => parseInt(id));
                    const deletedAdoptIds = currentAdoptIds.filter(id => !newAdoptIds.includes(id));
                    deletedAdoptIds.forEach(id => {
                        $(`div[data-image-id="${id}"]`).remove();
                        console.log('Removed deleted adoptable animal, ID:', id);
                    });
                    currentAdoptIds = newAdoptIds;
                    if (currentAdoptIds.length === 0 && !$('#no-adopt-images').length) {
                        $('#adopt-gallery').append('<p class="text-center text-gray-500 py-8" id="no-adopt-images">No adoptable animals yet. Check back soon!</p>');
                        console.log('Added no-adopt-images placeholder');
                    }
                } else if (response.adopt_ids && !Array.isArray(response.adopt_ids)) {
                    console.error('Invalid adopt_ids format:', response.adopt_ids);
                    debugMessage.text('Invalid server response format for adopt_ids. Check console.').show();
                    setTimeout(() => debugMessage.fadeOut(), 5000);
                }

                // Check if more images are available for loading
                if (response.total_adopt_count > offset) {
                    $('#load-more').show();
                } else {
                    $('#load-more').hide();
                }
            },
            error: function(xhr, status, error) {
                console.error('Polling error for adoptable animals:', status, error, xhr.status, xhr.responseText);
                const debugMessage = $('#debug-message');
                debugMessage.text(`Polling failed: ${status} (${xhr.status}). Retrying...`).show();
                setTimeout(() => debugMessage.fadeOut(), 5000);
            },
            complete: function() {
                setTimeout(pollImages, 5000); // Poll every 5 seconds
            }
        });
    }

    // Load more images
    $('#load-more').on('click', function() {
        console.log('Loading more adoptable animals, offset:', offset);
        $.ajax({
            url: 'fetch_images.php',
            type: 'GET',
            data: { offset: offset, limit: limit, load_more: true },
            dataType: 'json',
            success: function(response) {
                console.log('Load more response:', response);
                const gallery = $('#adopt-gallery');
                if (response.adopt_images && Array.isArray(response.adopt_images) && response.adopt_images.length > 0) {
                    response.adopt_images.forEach(animal => {
                        const animalId = parseInt(animal.id);
                        if (!currentAdoptIds.includes(animalId) && animal.image_data) {
                            const newCard = `
                                <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 hover:shadow-xl transition-shadow" data-image-id="${animal.id}">
                                    <img data-src="data:image/jpeg;base64,${animal.image_data}" alt="Adoptable animal" class="w-full h-48 object-cover rounded-lg mb-4 lazy-img" onerror="this.src='https://placehold.co/300x200?text=Image+Not+Found';this.onerror=null;" />
                                    <div class="flex justify-between items-center">
                                        <a href="https://www.facebook.com/messages/t/315034048360145" target="_blank" class="bg-home-green text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">Support Me</a>
                                    </div>
                                </div>`;
                            gallery.append(newCard);
                            currentAdoptIds.push(animalId);
                            console.log('Added more adoptable animal, ID:', animal.id);
                        }
                    });
                    offset += response.adopt_images.length;
                    lazyLoadImages();
                    if (response.total_adopt_count <= offset) {
                        $('#load-more').hide();
                    }
                } else {
                    console.log('No more adoptable images to load');
                    $('#load-more').hide();
                }
            },
            error: function(xhr, status, error) {
                console.error('Load more error:', status, error, xhr.status, xhr.responseText);
                const debugMessage = $('#debug-message');
                debugMessage.text(`Failed to load more: ${status} (${xhr.status}). Try again.`).show();
                setTimeout(() => debugMessage.fadeOut(), 5000);
            }
        });
    });

    // Start polling
    pollImages();
    </script>