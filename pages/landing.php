<!-- pages/landing.php -->
<!-- This script configures Tailwind CSS to use Bootstrap's theme attribute for dark mode -->

<style>
    /* Custom styles for the logo text */
    .tecn-text { color: #4586A4; }
    .ok-text { color: #809A50; }
    .ey-text { color: #CB2C52; }

    /* Styles for the DB part, now using Bootstrap's data-bs-theme attribute */
    .db-text {
        color: #312D2E; /* Black for light theme */
    }
    [data-bs-theme="dark"] .db-text {
        color: #FFFEFF; /* White for dark theme */
    }

    /* Simple fade-in animation */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .fade-in-up {
        animation: fadeInUp 0.8s both;
    }

    /* Tailwind styles will be applied via CDN, but this ensures body text colors are consistent with the theme */
    .landing-main {
        font-family: 'Inter', sans-serif;
        color: #312D2E;
    }
    [data-bs-theme="dark"] body {
        color: #FFFEFF;
    }

</style>

<!-- Main Content -->
<!-- We remove the <main> tag from here and control it from index.php for consistency -->
<div class="landing-main min-h-screen flex flex-col items-center justify-center text-center p-4">
    <div class="max-w-4xl w-full">

        <!-- Stylized Title -->
        <h1 class="text-5xl sm:text-7xl md:text-8xl font-black tracking-tighter fade-in-up delay-1">
            <span class="tecn-text">TECN</span><span class="ok-text">OK</span><span class="ey-text">EY</span><span class="db-text">DB</span>
        </h1>

        <!-- Subtitle -->
        <p class="mt-4 text-lg md:text-xl text-slate-600 dark:text-slate-400 max-w-2xl mx-auto fade-in-up delay-2">
            Solución integral para cerrajería Tecnokey.
        </p>

        <!-- CTA Button -->
        <div class="mt-10 fade-in-up delay-3">
            <a href="index.php?page=dashboard" class="bg-indigo-600 text-white font-bold py-3 px-8 rounded-lg text-lg hover:bg-indigo-700 transition-transform transform hover:scale-105 shadow-lg hover:shadow-indigo-500/50">
                Ir al panel principal
            </a>
        </div>

        <p class="mt-10 text-md md:text-s text-slate-600 dark:text-slate-400 max-w-2xl mx-auto fade-in-up delay-2">
            Consultas a Facundo :)
        </p>

    </div>
</div>

<!-- Since index.php already loads tailwind, we can remove the script tag from here if you add it there. -->
<!-- For now, leaving it here makes this component self-contained. -->
<script src="https://cdn.tailwindcss.com"></script>
