<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Home4Paws | Animal Rescue & Welfare in Camarines Sur</title>
    <meta name="description"
        content="Home4Paws is a non-profit animal rescue organization in Camarines Sur dedicated to spay/neuter, adoption, and animal welfare education." />
    <meta name="keywords"
        content="animal rescue, pet adoption, Camarines Sur, spay and neuter, animal welfare, Home4Paws" />
    <meta name="robots" content="index, follow" />
    <meta property="og:title" content="Home4Paws | Animal Rescue & Welfare" />
    <meta property="og:description"
        content="Support animal rescue efforts in Camarines Sur through donations, adoptions, and education." />
    <!-- <meta property="og:image" content="https://yourdomain.com/assets/images/social-preview.jpg" /> -->
    <!-- <meta property="og:url" content="https://yourdomain.com" /> -->
    <meta property="og:type" content="website" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="Home4Paws | Animal Rescue & Welfare" />
    <meta name="twitter:description"
        content="Support animal rescue efforts in Camarines Sur through donations, adoptions, and education." />
    <!-- <meta name="twitter:image" content="https://yourdomain.com/assets/images/social-preview.jpg" /> -->
    <!-- <link rel="icon" href="/favicon.ico" type="image/x-icon" /> -->
    <link rel="stylesheet" href="./src/output.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/lightbox2@2/dist/css/lightbox.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>

<body class="font-inter">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-6">
                <div class="text-xl font-bold text-[#718a47]">Home4Paws</div>
                <div class="md:hidden">
                    <button id="menu-button" class="text-gray-800 focus:outline-none">
                        <!-- Hamburger icon -->
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
                <ul id="nav-links"
                    class="hidden md:flex space-x-8 flex-col md:flex-row absolute md:static top-full left-0 w-full md:w-auto bg-white md:bg-transparent px-4 md:px-0 shadow-md md:shadow-none">
                    <li>
                        <a href="#home" class="block py-2 md:py-0 hover:text-[#718a47] transition">Home</a>
                    </li>
                    <li>
                        <a href="#about" class="block py-2 md:py-0 hover:text-[#718a47] transition">About</a>
                    </li>
                    <li>
                        <a href="#animals" class="block py-2 md:py-0 hover:text-[#718a47] transition">Animals</a>
                    </li>
                    <li>
                        <a href="#donate" class="block py-2 md:py-0 hover:text-[#718a47] transition">Donate</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Banner -->
    <section id="home"
        class="bg-[url('../assets/images/hero-banner.png')] bg-cover bg-center h-screen flex items-center justify-start text-white">
        <div class="max-w-7xl mx-auto px-4 py-20 text-center">
            <h1 class="text-7xl font-bold mb-4 leading-none">
                Hope for Paws serve to protect Animals
            </h1>
            <p class="text-xl mb-8">
                Spay /neuter, adoption drive, rescuing, information about animal
                rights and welfare
            </p>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-20 lg:py-0 min-h-[70dvh] grid place-content-center bg-gray-50">
        <div class="max-w-7xl mx-auto px-6">
            <h2 class="text-4xl md:text-5xl font-bold text-[#718a47] mb-12">
                About Us
            </h2>
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div>
                    <p class="text-lg md:text-xl text-gray-700 mb-6 leading-relaxed">
                        <strong class="text-[#718a47]">Home4Paws</strong> is a dedicated
                        animal rescue organization based in Camarines Sur (4402). We focus
                        on spay/neuter programs to control pet overpopulation, organize
                        adoption drives to find loving homes, and rescue animals from the
                        streets and abusive environments.
                    </p>
                    <p class="text-lg md:text-xl text-gray-700 leading-relaxed">
                        We also advocate for animal rights and welfare by educating
                        communities on compassionate care and the legal protections our
                        furry friends deserve.
                    </p>
                </div>

                <!-- Mission Card -->
                <div class="bg-white shadow-md border-l-4 border-[#718a47] p-8 rounded-lg">
                    <h3 class="text-2xl font-semibold text-[#718a47] mb-4">
                        Our Mission
                    </h3>
                    <ul class="space-y-4 text-gray-700">
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-[#718a47] mr-3 mt-1" fill="none" stroke="currentColor"
                                stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            Promote spay/neuter awareness
                        </li>
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-[#718a47] mr-3 mt-1" fill="none" stroke="currentColor"
                                stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            Host adoption events
                        </li>
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-[#718a47] mr-3 mt-1" fill="none" stroke="currentColor"
                                stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            Rescue and rehabilitate animals
                        </li>
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-[#718a47] mr-3 mt-1" fill="none" stroke="currentColor"
                                stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            Advocate for animal welfare
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- adopt animals -->
    <?php include './components/adopt.php'; ?>

    <!-- gallery (rescued animals) -->
    <?php include './components/gallery.php'; ?>


    <!-- Donate Section -->
    <section id="donate" class="py-20 bg-[#718a47] text-white">
        <div class="max-w-5xl mx-auto px-6 text-center">
            <h2 class="text-4xl font-bold mb-6">Support Our Cause</h2>
            <p class="text-base mb-12 max-w-3xl mx-auto">
                Your donations help fund spay/neuter surgeries, rescues, and animal
                welfare programs. Every contribution makes a big difference.
            </p>

            <div class="grid md:grid-cols-2 gap-8 max-w-3xl mx-auto text-left">
                <!-- GCash Option -->
                <div class="bg-white/20 p-6 rounded-lg backdrop-blur-sm shadow-lg">
                    <h3 class="text-xl font-semibold mb-2">Donate via GCash</h3>
                    <p class="text-lg mb-4">
                        <strong>Number:</strong> +63 939 090 7442
                    </p>

                    <!-- QR Code with hover effect -->
                    <div class="relative group w-fit mx-auto">
                        <img src="./assets/images/gcash-qr.jpg" alt="GCash QR Code"
                            class="mt-2 rounded shadow-md transition duration-300" />
                        <a href="./assets/images/gcash-qr.jpg" download="gcash-qr.jpg"
                            class="absolute inset-0 flex items-center justify-center bg-black/60 text-white font-medium opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded">
                            Download QR Code
                        </a>
                    </div>
                </div>

                <!-- Bank Transfer Option -->
                <div class="bg-white/20 p-6 rounded-lg backdrop-blur-sm shadow-lg">
                    <h3 class="text-xl font-semibold mb-2">Bank Transfer</h3>
                    <p class="text-lg leading-relaxed">
                        <strong>Account Name:</strong> J. A Abejero<br />
                        <strong>Bank:</strong> Asia United Bank (AUB)<br />
                        <strong>Account No.:</strong> 917-10-469890-1
                    </p>
                </div>
            </div>

            <!-- CTA Button -->
            <div class="mt-12">
                <a href="https://www.facebook.com/messages/t/315034048360145" target="_blank"
                    class="inline-block bg-white text-[#718a47] font-semibold px-6 py-3 rounded hover:bg-gray-100 transition">
                    Contact Us for More Info
                </a>
            </div>
        </div>
    </section>



    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="mt-4 text-sm">&copy; 2025 Home4Paws. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/lightbox2@2/dist/js/lightbox-plus-jquery.min.js"></script>
    <script src="./assets/js/index.js"></script>
</body>

</html>