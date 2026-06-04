<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: /~techcamp/inventory/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
	<style>
		body { background-color: #f0f2f5; }

        /* Sidebar */
        .sidebar {
          width: 250px;
          min-height: 100vh;
          background: linear-gradient(180deg, #2c3e50 0%, #3498db 100%);
          position: fixed;
          top: 0;
          left: 0;
          z-index: 1000;
          transition: all 0.3s ease;
          overflow-y: auto;
          overflow-x: hidden;
        }
        
        /* Sidebar Collapsed */
        .sidebar.collapsed {
          width: 65px;
        }
        
        .sidebar.collapsed .nav-text {
          display: none;
        }
        
        .sidebar.collapsed .brand-text {
          display: none;
        }
        
        .sidebar.collapsed .nav-link {
          justify-content: center;
          padding: 12px;
        }
        
        .sidebar.collapsed .nav-link i {
          width: auto;
          font-size: 1.2rem;
        }
        
        .sidebar.collapsed hr {
          margin: 10px 5px;
        }
        
        .sidebar .brand {
          padding: 18px 20px;
          color: white;
          font-size: 1.2rem;
          font-weight: bold;
          border-bottom: 1px solid rgba(255,255,255,0.1);
          display: flex;
          align-items: center;
          justify-content: space-between;
          min-height: 60px;
        }
        
        .sidebar .nav-link {
          color: rgba(255,255,255,0.8);
          padding: 12px 20px;
          display: flex;
          align-items: center;
          gap: 10px;
          transition: all 0.2s;
          white-space: nowrap;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
          color: white;
          background: rgba(255,255,255,0.15);
          padding-left: 28px;
        }
        
        .sidebar.collapsed .nav-link:hover,
        .sidebar.collapsed .nav-link.active {
          padding-left: 12px;
        }
        
        .sidebar .nav-link i {
          width: 20px;
          text-align: center;
          flex-shrink: 0;
        }
        
        /* Collapse Button */
        .collapse-btn {
          background: none;
          border: none;
          color: white;
          cursor: pointer;
          padding: 5px;
          border-radius: 5px;
          transition: all 0.2s;
          flex-shrink: 0;
        }
        
        .collapse-btn:hover {
          background: rgba(255,255,255,0.2);
        }
        
        /* Main Content */
        .main-content {
          margin-left: 250px;
          padding: 20px;
          min-height: 100vh;
          transition: all 0.3s ease;
        }
        
        .main-content.expanded {
          margin-left: 65px;
        }
        
        /* Topbar */
        .topbar {
          background: white;
          padding: 15px 25px;
          margin: -20px -20px 20px -20px;
          box-shadow: 0 2px 5px rgba(0,0,0,0.08);
          display: flex;
          justify-content: space-between;
          align-items: center;
          flex-wrap: wrap;
          gap: 10px;
        }
        
        /* Hamburger */
        .hamburger {
          display: none;
          background: none;
          border: none;
          font-size: 1.5rem;
          color: #2c3e50;
          cursor: pointer;
          padding: 5px;
        }
        
        /* Overlay */
        .sidebar-overlay {
          display: none;
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background: rgba(0,0,0,0.5);
          z-index: 999;
        }
        
        .sidebar-overlay.active {
          display: block;
        }
        
        /* Cards */
        .stat-card {
          border-radius: 12px;
          border: none;
          box-shadow: 0 4px 12px rgba(0,0,0,0.08);
          transition: transform 0.2s;
        }
        
        .stat-card:hover {
          transform: translateY(-3px);
        }
        
        .stat-icon {
          width: 60px;
          height: 60px;
          border-radius: 12px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 1.5rem;
          color: white;
          flex-shrink: 0;
        }
        
        .table-card {
          border-radius: 12px;
          border: none;
          box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .badge-low {
          background: #fff3cd;
          color: #856404;
        }
        
        /* Tooltip for collapsed sidebar */
        .sidebar.collapsed .nav-link {
          position: relative;
        }
        
        /* Mobile */
        @media (max-width: 768px) {
          .sidebar {
            left: -250px;
            width: 250px !important;
          }
        
          .sidebar.active {
            left: 0;
            box-shadow: 5px 0 15px rgba(0,0,0,0.3);
          }
        
          .sidebar.collapsed {
            left: -250px;
            width: 250px !important;
          }
        
          .sidebar.collapsed .nav-text {
            display: inline !important;
          }
        
          .sidebar.collapsed .brand-text {
            display: inline !important;
          }
        
          .sidebar.collapsed .nav-link {
            justify-content: flex-start;
            padding: 12px 20px;
          }
        
          .collapse-btn {
            display: none;
          }
        
          .main-content {
            margin-left: 0 !important;
            padding: 15px;
          }
        
          .topbar {
            margin: -15px -15px 15px -15px;
            padding: 12px 15px;
          }
        
          .hamburger {
            display: block;
          }
        
          .stat-icon {
            width: 45px;
            height: 45px;
            font-size: 1.2rem;
          }
        }
        
        @media (max-width: 480px) {
          .main-content { padding: 10px; }
          .topbar {
            margin: -10px -10px 10px -10px;
            padding: 10px;
          }
        }
        
        @media print {
          .sidebar, .hamburger, .sidebar-overlay,
          .collapse-btn, .no-print { display: none !important; }
          .main-content { margin-left: 0 !important; }
          .card { border: 1px solid #ddd !important; box-shadow: none !important; }
        }
	</style>
</head>
<body>