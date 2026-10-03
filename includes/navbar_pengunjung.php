<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="jp-navbar">

    <div class="jp-logo" id="secretLogo">
        Jelly Potter 🪄✨
    </div>

    <button class="jp-menu-toggle" id="menuToggle">
        ☰
    </button>

   <div class="jp-nav-links" id="navLinks">
    <a href="index.php#home" class="<?= ($currentPage == 'index.php') ? 'active' : '' ?>">
        Home
    </a>

    <a href="index.php#menu">
        Featured
    </a>

    <a href="katalog.php" class="<?= ($currentPage == 'katalog.php') ? 'active' : '' ?>">
        Menu
    </a>

    <a href="index.php#gallery">
        Gallery
    </a>

    <a href="index.php#about">
        About
    </a>
</div>

</nav>

<style>
    .jp-navbar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 9999;

        display: flex;
        justify-content: space-between;
        align-items: center;

        padding: 18px 50px;

        background: rgba(255, 253, 249, 0.72);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);

        border-bottom: 1px solid rgba(255,255,255,0.45);
        box-shadow: 0 8px 30px rgba(92,58,33,0.08);

        transition: 0.3s ease;
    }

    .jp-navbar.scrolled {
        padding: 12px 40px;
        box-shadow: 0 10px 35px rgba(92,58,33,0.12);
    }

    .jp-logo {
        font-family: 'Playfair Display', serif;
        font-size: 1.7rem;
        font-weight: 900;
        color: #5C3A21;
        cursor: pointer;
        user-select: none;
        transition: 0.3s ease;
    }

    .jp-logo:hover {
        transform: scale(1.03);
    }

    .jp-nav-links {
        display: flex;
        align-items: center;
        gap: 32px;
    }

    .jp-nav-links a {
        text-decoration: none;
        color: #8d735b;
        font-weight: 700;
        font-size: 0.98rem;
        position: relative;
        transition: 0.25s ease;
    }

    .jp-nav-links a::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: -6px;
        width: 0;
        height: 2px;
        background: #5C3A21;
        transition: 0.25s ease;
    }

    .jp-nav-links a:hover,
    .jp-nav-links a.active {
        color: #5C3A21;
    }

    .jp-nav-links a:hover::after,
    .jp-nav-links a.active::after {
        width: 100%;
    }

    .jp-menu-toggle {
        display: none;
        border: none;
        background: transparent;
        font-size: 1.8rem;
        cursor: pointer;
        color: #5C3A21;
    }

    @media (max-width: 768px) {
        .jp-navbar {
            padding: 15px 20px;
        }

        .jp-logo {
            font-size: 1.2rem;
        }

        .jp-menu-toggle {
            display: block;
        }

        .jp-nav-links {
            position: absolute;
            top: 100%;
            left: 15px;
            right: 15px;

            flex-direction: column;
            gap: 18px;

            background: rgba(255,253,249,0.95);
            backdrop-filter: blur(16px);

            padding: 20px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(92,58,33,0.12);

            opacity: 0;
            pointer-events: none;
            transform: translateY(-10px);

            transition: 0.25s ease;
        }

        .jp-nav-links.show {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }
    }
</style>

<script>
    /*
    ========================
    MOBILE MENU
    ========================
    */
    const menuToggle = document.getElementById("menuToggle");
    const navLinks = document.getElementById("navLinks");

    menuToggle.addEventListener("click", () => {
        navLinks.classList.toggle("show");
    });

    /*
    ========================
    NAVBAR SHRINK
    ========================
    */
    window.addEventListener("scroll", () => {
        const navbar = document.querySelector(".jp-navbar");

        if (window.scrollY > 40) {
            navbar.classList.add("scrolled");
        } else {
            navbar.classList.remove("scrolled");
        }
    });

    /*
    ========================
    SECRET LOGO
    ========================
    */
    let clickCount = 0;
    let resetTimer;

    document.getElementById("secretLogo").addEventListener("click", function() {
        clickCount++;
        clearTimeout(resetTimer);

        if (clickCount >= 5) {
            Swal.fire({
                html: `
                    <div style="font-family:'Quicksand', sans-serif;">
                        <div style="font-size:50px;">🪄</div>
                        <h2 style="
                            font-family:'Playfair Display', serif;
                            color:#5C3A21;
                            margin:10px 0;
                        ">
                            Magic Portal Opened
                        </h2>
                        <p style="
                            color:#8d735b;
                            font-size:16px;
                            margin:0;
                        ">
                            Redirecting staff...
                        </p>
                    </div>
                `,
                background: '#FFFDF9',
                showConfirmButton: false,
                timer: 1800,
                width: '420px',
                backdrop: `
                    rgba(92,58,33,0.35)
                    blur(8px)
                `
            }).then(() => {
                window.location.href = "../auth/login.php";
            });

            clickCount = 0;
        }

        resetTimer = setTimeout(() => {
            clickCount = 0;
        }, 3000);
    });

    /*
    ========================
    AUTO CLOSE MOBILE MENU
    ========================
    */
    document.querySelectorAll('.jp-nav-links a').forEach(link => {
        link.addEventListener('click', () => {
            navLinks.classList.remove('show');
        });
    });
</script>