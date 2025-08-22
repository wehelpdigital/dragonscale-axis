<!-- ========== Left Sidebar Start ========== -->
<div class="vertical-menu">

    <div data-simplebar class="h-100">

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title" key="t-menu">@lang('translation.Menu')</li>

                <!-- Crypto Checker Navigation -->
                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bx-bitcoin"></i>
                        <span key="t-crypto-checker">Crypto Checker</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li>
                            <a href="{{ route('crypto-set') }}" class="waves-effect">
                                <i class="bx bx-cog"></i>
                                <span key="t-crypto-set">Set</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('crypto-notification-history') }}" class="waves-effect">
                                <i class="bx bx-bell"></i>
                                <span key="t-crypto-notification-history">Notification History</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('crypto-history') }}" class="waves-effect">
                                <i class="bx bx-history"></i>
                                <span key="t-crypto-history">Coin Price History</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('crypto-pricing-history') }}" class="waves-effect">
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
                                    <a href="{{ route('crypto-difference-history-to-buy') }}" class="waves-effect">
                                        <i class="bx bx-up-arrow-circle"></i>
                                        <span key="t-crypto-difference-history-to-buy">To Buy</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('crypto-difference-history-to-sell') }}" class="waves-effect">
                                        <i class="bx bx-down-arrow-circle"></i>
                                        <span key="t-crypto-difference-history-to-sell">To Sell</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <a href="{{ route('crypto-difference-analysis') }}" class="waves-effect">
                                <i class="bx bx-bar-chart-alt-2"></i>
                                <span key="t-crypto-difference-analysis">Difference Analysis</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('crypto-income-logger') }}" class="waves-effect">
                                <i class="bx bx-dollar-circle"></i>
                                <span key="t-crypto-income-logger">Income Logger</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('crypto-tutorials') }}" class="waves-effect">
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
                    <a href="{{ route('anisenso-courses') }}" class="waves-effect">
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

                <!-- Ani-Senso E-com Navigation -->
                <li>
                    <a href="javascript: void(0);" class="waves-effect">
                        <i class="bx bx-store"></i>
                        <span key="t-ani-senso-ecom">Ani-Senso E-com</span>
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

                <!-- Users Navigation -->
                <li>
                    <a href="{{ route('users.index') }}" class="waves-effect">
                        <i class="bx bx-user"></i>
                        <span key="t-users">Users</span>
                    </a>
                </li>

            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
<!-- Left Sidebar End -->
