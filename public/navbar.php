<?php
// Inicia a sessão se não estiver iniciada
require_once __DIR__ . "/../src/Model/utilizador.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    nav.navbar {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.2);
        padding: 1rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .navbar-brand {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1.5rem;
        font-weight: bold;
        color: #fff;
        text-decoration: none;
        transition: transform 0.3s ease;
    }

    .navbar-brand:hover {
        transform: scale(1.05);
    }

    .navbar-brand::before {
        content: "📈";
        font-size: 1.8rem;
    }

    .navbar-links {
        display: flex;
        list-style: none;
        gap: 0;
        align-items: center;
        flex-wrap: wrap;
    }

    .navbar-links li a {
        color: #fff;
        text-decoration: none;
        padding: 0.75rem 1.5rem;
        transition: all 0.3s ease;
        font-weight: 500;
        position: relative;
    }

    .navbar-links li a::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 0;
        height: 2px;
        background: #4CAF50;
        transition: width 0.3s ease;
    }

    .navbar-links li a:hover {
        background-color: rgba(76, 175, 80, 0.2);
    }

    .navbar-links li a:hover::after {
        width: 100%;
    }

    .navbar-user-section {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .user-info {
        color: #fff;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .user-info strong {
        color: #4CAF50;
    }

    .logout-btn {
        background-color: #d32f2f;
        color: white;
        padding: 0.6rem 1.5rem;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        font-size: 0.95rem;
    }

    .logout-btn:hover {
        background-color: #b71c1c;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(211, 47, 47, 0.3);
    }

    .navbar-toggle {
        display: none;
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
    }

    @media (max-width: 768px) {
        nav.navbar {
            flex-direction: column;
            gap: 1rem;
        }

        .navbar-links {
            width: 100%;
            flex-direction: column;
            display: none;
        }

        .navbar-links.active {
            display: flex;
        }

        .navbar-links li a {
            padding: 0.5rem 1rem;
            width: 100%;
        }

        .navbar-toggle {
            display: block;
        }

        .navbar-user-section {
            width: 100%;
            justify-content: space-between;
        }
    }
</style>

<nav class="navbar">
    <a href="index.php" class="navbar-brand">Paper Trading</a>

    <button class="navbar-toggle" id="navbarToggle">☰</button>

    <ul class="navbar-links" id="navbarLinks">
        <li><a href="/investimento/public/index.php">📊 Home</a></li>
        <?php if (isset($_SESSION['utilizador'])): ?>
            <li><a href="/investimento/public/dashboard.php">💼 Dashboard</a></li>
            <li><a href="/investimento/public/explorar.php">📈 Paper Trading Real</a></li>
        <?php else: ?>
            <li><a href="/investimento/public/explorar.php">📈 Paper Trading Real</a></li>
            <li><a href="/investimento/public/login.php">🔐 Login</a></li>
            <li><a href="/investimento/public/register.php">✍️ Registro</a></li>
        <?php endif; ?>
    </ul>

    <div class="navbar-user-section">
        <?php if (isset($_SESSION['utilizador'])): ?>
            <div class="user-info">
                👤 <strong><?php echo htmlspecialchars($_SESSION['utilizador']->getUsername()); ?></strong> | 💰 $ <?php echo number_format($_SESSION['utilizador']->getSaldoReal(), 2); ?>
            </div>
            <a href="/investimento/public/logout.php" class="logout-btn">🚪 Logout</a>
        <?php endif; ?>
    </div>
</nav>

<script>
    document.getElementById('navbarToggle').addEventListener('click', function() {
        const navbarLinks = document.getElementById('navbarLinks');
        navbarLinks.classList.toggle('active');
    });

    // Fechar menu ao clicar em um link (mobile)
    document.querySelectorAll('.navbar-links a').forEach(link => {
        link.addEventListener('click', function() {
            document.getElementById('navbarLinks').classList.remove('active');
        });
    });
</script>
