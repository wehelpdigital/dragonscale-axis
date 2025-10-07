<!-- ========== Left Sidebar Start ========== -->
<div class="vertical-menu">

    <div data-simplebar class="h-100">

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title" key="t-menu"><?php echo app('translator')->get('translation.Menu'); ?></li>

                <!-- Crypto Checker Navigation -->
                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bx-bitcoin"></i>
                        <span key="t-crypto-checker">Crypto Checker</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li>
                            <a href="<?php echo e(route('crypto-set')); ?>" class="waves-effect">
                                <i class="bx bx-cog"></i>
                                <span key="t-crypto-set">Set</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e(route('crypto-notification-history')); ?>" class="waves-effect">
                                <i class="bx bx-bell"></i>
                                <span key="t-crypto-notification-history">Notification History</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e(route('crypto-history')); ?>" class="waves-effect">
                                <i class="bx bx-history"></i>
                                <span key="t-crypto-history">Coin Price History</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e(route('crypto-pricing-history')); ?>" class="waves-effect">
                                <i class="bx bx-trending-up"></i>
                                <span key="t-crypto-pricing-history">Ladder History</span>
                            </a>
                        </li>
                        <li>
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="bx bx-line-chart"></i>
                                <span key="t-crypto-difference-history">Difference History</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="false">
                                <li>
                                    <a href="<?php echo e(route('crypto-difference-history-to-buy')); ?>" class="waves-effect">
                                        <i class="bx bx-up-arrow-circle"></i>
                                        <span key="t-crypto-difference-history-to-buy">To Buy</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="<?php echo e(route('crypto-difference-history-to-sell')); ?>" class="waves-effect">
                                        <i class="bx bx-down-arrow-circle"></i>
                                        <span key="t-crypto-difference-history-to-sell">To Sell</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <a href="<?php echo e(route('crypto-difference-analysis')); ?>" class="waves-effect">
                                <i class="bx bx-bar-chart-alt-2"></i>
                                <span key="t-crypto-difference-analysis">Difference Analysis</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e(route('crypto-income-logger')); ?>" class="waves-effect">
                                <i class="bx bx-dollar-circle"></i>
                                <span key="t-crypto-income-logger">Income Logger</span>
                            </a>
                        </li>

                        <li>
                            <a href="<?php echo e(route('crypto-tutorials')); ?>" class="waves-effect">
                                <i class="bx bx-book-reader"></i>
                                <span key="t-crypto-tutorials">Tutorials</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Divider -->
                <li class="menu-title">─</li>

                <!-- Ani-Senso Course Navigation -->
                <li>
                    <a href="<?php echo e(route('anisenso-courses')); ?>" class="waves-effect">
                        <i class="bx bx-book-open"></i>
                        <span key="t-ani-senso-course">Ani-Senso Course</span>
                    </a>
                </li>

                <!-- Ani-Senso Tools Navigation -->
                <li>
                    <a href="javascript: void(0);" class="waves-effect">
                        <i class="bx bx-wrench"></i>
                        <span key="t-ani-senso-tools">Ani-Senso Tools</span>
                    </a>
                </li>

                <!-- Ani-Senso Clients Navigation -->
                <li>
                    <a href="javascript: void(0);" class="waves-effect">
                        <i class="bx bx-group"></i>
                        <span key="t-ani-senso-clients">Ani-Senso Clients</span>
                    </a>
                </li>

                <!-- Divider -->
                <li class="menu-title">─</li>

                <!-- E-commerce Navigation -->
                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bx-shopping-bag"></i>
                        <span key="t-ecommerce">E-commerce</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li>
                            <a href="javascript: void(0);" class="waves-effect">
                                <i class="bx bx-store"></i>
                                <span key="t-stores">Stores</span>
                            </a>
                        </li>
                        <li>
                            <a href="javascript: void(0);" class="waves-effect">
                                <i class="bx bx-receipt"></i>
                                <span key="t-reports">Reports</span>
                            </a>
                        </li>
                        <li>
                            <a href="javascript: void(0);" class="waves-effect">
                                <i class="bx bx-undo"></i>
                                <span key="t-refunds">Refunds</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e(route('ecom-products')); ?>" class="waves-effect">
                                <i class="bx bx-package"></i>
                                <span key="t-products">Products</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e(route('ecom-orders')); ?>" class="waves-effect">
                                <i class="bx bx-cart"></i>
                                <span key="t-orders">Orders</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e(route('ecom-shipping')); ?>" class="waves-effect">
                                <i class="bx bx-car"></i>
                                <span key="t-shipping">Shipping</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e(route('ecom-discounts')); ?>" class="waves-effect">
                                <i class="bx bx-tag"></i>
                                <span key="t-discounts">Discounts</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e(route('ecom-affiliates')); ?>" class="waves-effect">
                                <i class="bx bx-group"></i>
                                <span key="t-affiliates">Affiliates</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e(route('ecom-triggers')); ?>" class="waves-effect">
                                <i class="bx bx-key"></i>
                                <span key="t-triggers">Triggers</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Divider -->
                <li class="menu-title">─</li>

                <!-- Admin Users Navigation -->
                <li>
                    <a href="<?php echo e(route('users.index')); ?>" class="waves-effect">
                        <i class="bx bx-user"></i>
                        <span key="t-users">Admin Users</span>
                    </a>
                </li>

            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
<!-- Left Sidebar End -->
<?php /**PATH C:\xampp\htdocs\btc-check\resources\views/layouts/sidebar.blade.php ENDPATH**/ ?>