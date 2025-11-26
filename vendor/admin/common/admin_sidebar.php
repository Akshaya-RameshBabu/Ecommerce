<?php
session_start(); 

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: admin_login.php');
    exit;
}
?>

<aside class="w-64 bg-indigo-800 text-white min-h-screen p-6 fixed">
    <h1 class="text-2xl font-bold mb-8">Admin Dashboard</h1>
    <nav class="space-y-4">
        <a href="ManageCrackers.php" class="block p-3 rounded hover:bg-indigo-700 transition-colors">Manage Crackers</a>
        <a href="AddCrackers.php" class="block p-3 rounded hover:bg-indigo-700 transition-colors">Add Cracker</a>
        <!-- <a href="ManageOrders.php" class="block p-3 rounded hover:bg-indigo-700 transition-colors">Manage Orders</a> -->
        <a href="ListOfEnquiries.php" class="block p-3 rounded hover:bg-indigo-700 transition-colors">List of Enquiries</a>
        <a href="Setting.php" class="block p-3 rounded hover:bg-indigo-700 transition-colors">Settings</a>
        <a href="Profile.php" class="block p-3 rounded hover:bg-indigo-700 transition-colors">Admin Profile</a>
        <form method="POST" action="logout.php" class="mt-4">
            <button type="submit" class="w-full bg-red-600 text-white p-3 rounded hover:bg-red-700 transition-colors">Logout</button>
        </form>
    </nav>
</aside>

<style>
    @media (max-width: 768px) {
        aside {
            width: 100%;
            position: relative;
        }
        .admin-main {
            margin-left: 0;
        }
    }
</style>