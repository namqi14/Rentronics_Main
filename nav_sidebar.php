<?php
if (!isset($_SESSION['auser'])) {
    header("Location: index.php");
    exit();
}

$access_level = $_SESSION['access_level']; // Assuming access level is stored in the session
?>

<!-- Add loading overlay at the top -->
<?php include 'loading/loading.php'; ?>

<div class="container-fluid bg-transparent px-0">
    <nav class="navbar navbar-expand-lg bg-dark navbar-light py-0 px-4">
        <div class="navbar-brand d-flex align-items-center text-center">
            <div class="icon p-2 me-2">
                <button class="mobile_btn" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <h1 class="m-0 text-light">Rentronic</h1>
        </div>
    </nav>

    <div class="sidebar bg-dark">
        <div class="sidebar-inner slimscroll">
            <div class="sidebar-logo-con">
                <img src="/rentronics/img/rentronics.jpg" alt="Logo" class="sidebar-logo-img">
            </div>
            <div id="sidebar-menu" class="sidebar-menu">
                <ul>
                    <li class="menu-title">
                        <span>Main</span>
                    </li>
                    <li class="sidebar-item submenu">
                        <a href="#" class="sidebar-link">
                            <i class="fas fa-home sidebar-logo"></i>
                            <span>Dashboard</span>
                            <span class="menu-arrow">
                                <i class="fas fa-chevron-right"></i>
                            </span>
                        </a>
                        <ul class="sidebar-dropdown">
                            <li><a href="<?php echo ($access_level == 1) ? '/rentronics/module-property/admin/dashboard.php' : '/rentronics/module-property/agent/dashboard/dashboardagent.php'; ?>" class="sidebar-link">
                                Dashboard</a></li>
                            <?php if ($access_level == 1): // Only Admin can see these links ?>
                            <li><a href="/rentronics/module-property/admin/dashboardproperty.php" class="sidebar-link mini">Dashboard Property</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <li class="menu-title">
                        <span>Property</span>
                    </li>
                    <li class="sidebar-item submenu">
                        <a href="#" class="sidebar-link">
                            <i class="fas fa-city sidebar-logo"></i>
                            <span>Property</span>
                            <span class="menu-arrow">
                                <i class="fas fa-chevron-right"></i>
                            </span>
                        </a>
                        <ul class="sidebar-dropdown">
                            <li>
                                <!-- Different links for Agent and Admin for Property List -->
                                <a href="<?php echo ($access_level == 1) ? '/rentronics/module-property/property/propertytable.php' : '/rentronics/module-property/agent/propertylist.php'; ?>" class="sidebar-link">
                                    Property List
                                </a>
                            </li>                            
                            <?php if ($access_level == 1): // Only Admin can see these links ?>
                            <li><a href="/rentronics/module-property/room/roomtable.php" class="sidebar-link mini">Room List</a></li>
                            <li><a href="/rentronics/module-property/bed/bedtable.php" class="sidebar-link mini">Bed List</a></li>
                            <li><a href="/rentronics/module-property/tenant/tenanttable.php" class="sidebar-link mini">Tenant List</a></li>
                            <?php endif; ?>
                            <?php if ($access_level == 2):?>
                            <li><a href="/rentronics/module-property/admin/dashboardproperty.php" class="sidebar-link mini">Property Management</a></li>
                            <li><a href="/rentronics/module-property/tenant/bookingform-download.php" class="sidebar-link mini">Tenant Agreement</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <li class="menu-title">
                        <span>Payment</span>
                    </li>
                    <li class="sidebar-item submenu">
                        <a href="#" class="sidebar-link">
                            <i class="fas fa-wallet sidebar-logo"></i>
                            <span>Payments</span>
                            <span class="menu-arrow">
                                <i class="fas fa-chevron-right"></i>
                            </span>
                        </a>
                        <ul class="sidebar-dropdown">
                            <?php if ($access_level == 1): // Only Admin can see these links ?>
                            <li><a href="/rentronics/module-payment/externalpayment.php" class="sidebar-link">Update Payment</a></li>
                            <?php endif; ?>
                            <li><a href="/rentronics/module-property/receipt/payment-table.php" class="sidebar-link">Payment History</a></li>
                            <?php if ($access_level == 1): // Only Admin can see these links ?>
                            <li><a href="/rentronics/module-payment/other-payment/other-payment.php" class="sidebar-link">Other Payment</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
        <div class="sidebar-footer">
            <a href="/rentronics/module-auth/logout.php" class="sidebar-link">
                <i class="lni lni-exit"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</div>

