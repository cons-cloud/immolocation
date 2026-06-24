<?php include '../../includes/header.php'; ?>

<div class="dashboard">

    <aside class="sidebar">
        <h2>Admin Panel</h2>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="users.php">Utilisateurs</a></li>
            <li><a href="annonces.php">Annonces</a></li>
            <li><a href="../logout.php">Déconnexion</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h1>Dashboard Admin</h1>

        <div class="cards">
            <div class="card">Utilisateurs: 120</div>
            <div class="card">Annonces: 45</div>
            <div class="card">Réservations: 18</div>
        </div>
    </main>

</div>

<?php include '../../includes/footer.php'; ?>