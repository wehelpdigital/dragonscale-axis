<!-- ========== Left Sidebar Start ========== -->
<div class="vertical-menu">

    <div data-simplebar class="h-100">

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title" key="t-menu">@lang('translation.Menu')</li>

                <!-- Crypto Checker Navigation -->
                <li class="{{ request()->is('crypto-*') ? 'mm-active' : '' }}">
                    <a href="javascript: void(0);" class="has-arrow waves-effect {{ request()->is('crypto-*') ? 'mm-active' : '' }}">
                        <i class="bx bx-bitcoin"></i>
                        <span key="t-crypto-checker">Crypto Checker</span>
                    </a>
                    <ul class="sub-menu {{ request()->is('crypto-*') ? 'mm-show' : '' }}" aria-expanded="{{ request()->is('crypto-*') ? 'true' : 'false' }}">
                        <li class="{{ request()->routeIs('crypto-set') ? 'mm-active' : '' }}">
                            <a href="{{ route('crypto-set') }}" class="waves-effect {{ request()->routeIs('crypto-set') ? 'active' : '' }}">
                                <i class="bx bx-cog"></i>
                                <span key="t-crypto-set">Set</span>
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('crypto-notification-history') ? 'mm-active' : '' }}">
                            <a href="{{ route('crypto-notification-history') }}" class="waves-effect {{ request()->routeIs('crypto-notification-history') ? 'active' : '' }}">
                                <i class="bx bx-bell"></i>
                                <span key="t-crypto-notification-history">Notification History</span>
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('crypto-history') ? 'mm-active' : '' }}">
                            <a href="{{ route('crypto-history') }}" class="waves-effect {{ request()->routeIs('crypto-history') ? 'active' : '' }}">
                                <i class="bx bx-history"></i>
                                <span key="t-crypto-history">Coin Price History</span>
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('crypto-pricing-history') ? 'mm-active' : '' }}">
                            <a href="{{ route('crypto-pricing-history') }}" class="waves-effect {{ request()->routeIs('crypto-pricing-history') ? 'active' : '' }}">
                                <i class="bx bx-trending-up"></i>
                                <span key="t-crypto-pricing-history">Ladder History</span>
                            </a>
                        </li>
                        <li class="{{ request()->is('crypto-difference-history*') ? 'mm-active' : '' }}">
                            <a href="javascript: void(0);" class="has-arrow waves-effect {{ request()->is('crypto-difference-history*') ? 'mm-active' : '' }}">
                                <i class="bx bx-line-chart"></i>
                                <span key="t-crypto-difference-history">Difference History</span>
                            </a>
                            <ul class="sub-menu {{ request()->is('crypto-difference-history*') ? 'mm-show' : '' }}" aria-expanded="{{ request()->is('crypto-difference-history*') ? 'true' : 'false' }}">
                                <li class="{{ request()->routeIs('crypto-difference-history-to-buy') ? 'mm-active' : '' }}">
                                    <a href="{{ route('crypto-difference-history-to-buy') }}" class="waves-effect {{ request()->routeIs('crypto-difference-history-to-buy') ? 'active' : '' }}">
                                        <i class="bx bx-up-arrow-circle"></i>
                                        <span key="t-crypto-difference-history-to-buy">To Buy</span>
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('crypto-difference-history-to-sell') ? 'mm-active' : '' }}">
                                    <a href="{{ route('crypto-difference-history-to-sell') }}" class="waves-effect {{ request()->routeIs('crypto-difference-history-to-sell') ? 'active' : '' }}">
                                        <i class="bx bx-down-arrow-circle"></i>
                                        <span key="t-crypto-difference-history-to-sell">To Sell</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="{{ request()->routeIs('crypto-difference-analysis') ? 'mm-active' : '' }}">
                            <a href="{{ route('crypto-difference-analysis') }}" class="waves-effect {{ request()->routeIs('crypto-difference-analysis') ? 'active' : '' }}">
                                <i class="bx bx-bar-chart-alt-2"></i>
                                <span key="t-crypto-difference-analysis">Difference Analysis</span>
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('crypto-income-logger') ? 'mm-active' : '' }}">
                            <a href="{{ route('crypto-income-logger') }}" class="waves-effect {{ request()->routeIs('crypto-income-logger') ? 'active' : '' }}">
                                <i class="bx bx-dollar-circle"></i>
                                <span key="t-crypto-income-logger">Income Logger</span>
                            </a>
                        </li>

                        <li class="{{ request()->routeIs('crypto-tutorials') ? 'mm-active' : '' }}">
                            <a href="{{ route('crypto-tutorials') }}" class="waves-effect {{ request()->routeIs('crypto-tutorials') ? 'active' : '' }}">
                                <i class="bx bx-book-reader"></i>
                                <span key="t-crypto-tutorials">Tutorials</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Divider -->
                <li class="menu-title">─</li>

                <!-- Ani-Senso Navigation -->
                <li class="{{ request()->is('anisenso*') ? 'mm-active' : '' }}">
                    <a href="javascript: void(0);" class="has-arrow waves-effect {{ request()->is('anisenso*') ? 'mm-active' : '' }}">
                        <i class="bx bx-play-circle"></i>
                        <span key="t-ani-senso">Ani-Senso</span>
                    </a>
                    <ul class="sub-menu {{ request()->is('anisenso*') ? 'mm-show' : '' }}" aria-expanded="{{ request()->is('anisenso*') ? 'true' : 'false' }}">
                        <li class="{{ request()->is('anisenso-courses*') || request()->is('anisenso-chapters*') || request()->is('anisenso-topics*') ? 'mm-active' : '' }}">
                            <a href="{{ route('anisenso-courses') }}" class="waves-effect {{ request()->is('anisenso-courses*') || request()->is('anisenso-chapters*') || request()->is('anisenso-topics*') ? 'active' : '' }}">
                                <i class="bx bx-book-open"></i>
                                <span key="t-ani-senso-course">Course</span>
                            </a>
                        </li>
                        <li class="{{ request()->is('ai-technician*') ? 'mm-active' : '' }}">
                            <a href="javascript: void(0);" class="has-arrow waves-effect {{ request()->is('ai-technician*') ? 'mm-active' : '' }}">
                                <i class="bx bx-bot"></i>
                                <span key="t-ai-technician">AI Technician</span>
                            </a>
                            <ul class="sub-menu {{ request()->is('ai-technician*') ? 'mm-show' : '' }}" aria-expanded="{{ request()->is('ai-technician*') ? 'true' : 'false' }}">
                                <li class="{{ request()->is('ai-technician-chat*') ? 'mm-active' : '' }}">
                                    <a href="{{ route('ai-technician.chat') }}" class="waves-effect {{ request()->is('ai-technician-chat*') ? 'active' : '' }}">
                                        <i class="bx bx-chat"></i>
                                        <span key="t-ai-chat">Chat</span>
                                    </a>
                                </li>
                                <li class="{{ request()->is('ai-technician-kb*') || request()->is('ai-technician-knowledge-base*') ? 'mm-active' : '' }}">
                                    <a href="{{ route('ai-technician.knowledge-base') }}" class="waves-effect {{ request()->is('ai-technician-kb*') || request()->is('ai-technician-knowledge-base*') ? 'active' : '' }}">
                                        <i class="bx bx-data"></i>
                                        <span key="t-ai-knowledgebase">Knowledge Base</span>
                                    </a>
                                </li>
                                <li class="{{ request()->is('ai-technician-reply-flow*') ? 'mm-active' : '' }}">
                                    <a href="{{ route('ai-technician.reply-flow') }}" class="waves-effect {{ request()->is('ai-technician-reply-flow*') ? 'active' : '' }}">
                                        <i class="bx bx-git-branch"></i>
                                        <span key="t-ai-reply-flow">Reply Flow</span>
                                    </a>
                                </li>
                                <li class="{{ request()->is('ai-technician-query-rules*') ? 'mm-active' : '' }}">
                                    <a href="{{ route('ai-technician.query-rules') }}" class="waves-effect {{ request()->is('ai-technician-query-rules*') ? 'active' : '' }}">
                                        <i class="bx bx-list-check"></i>
                                        <span key="t-ai-query-rules">Query Rules</span>
                                    </a>
                                </li>
                                <li class="{{ request()->is('ai-technician-clients*') ? 'mm-active' : '' }}">
                                    <a href="{{ route('ai-technician.clients') }}" class="waves-effect {{ request()->is('ai-technician-clients*') ? 'active' : '' }}">
                                        <i class="bx bx-user"></i>
                                        <span key="t-ai-clients">Clients</span>
                                    </a>
                                </li>
                                <li class="{{ request()->is('ai-technician-settings*') ? 'mm-active' : '' }}">
                                    <a href="{{ route('ai-technician.settings') }}" class="waves-effect {{ request()->is('ai-technician-settings*') ? 'active' : '' }}">
                                        <i class="bx bx-wrench"></i>
                                        <span key="t-ai-settings">Settings</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="{{ request()->is('recommendation*') ? 'mm-active' : '' }}">
                            <a href="javascript: void(0);" class="has-arrow waves-effect {{ request()->is('recommendation*') ? 'mm-active' : '' }}">
                                <i class="bx bx-bulb"></i>
                                <span key="t-recommendations">Recommendations</span>
                            </a>
                            <ul class="sub-menu {{ request()->is('recommendation*') ? 'mm-show' : '' }}" aria-expanded="{{ request()->is('recommendation*') ? 'true' : 'false' }}">
                                <li class="{{ request()->is('recommendation-generate*') ? 'mm-active' : '' }}">
                                    <a href="javascript: void(0);" class="waves-effect {{ request()->is('recommendation-generate*') ? 'active' : '' }}">
                                        <i class="bx bx-layer"></i>
                                        <span key="t-rec-generate">Generate Recom.</span>
                                    </a>
                                </li>
                                <li class="{{ request()->is('recommendation-scheduler*') ? 'mm-active' : '' }}">
                                    <a href="javascript: void(0);" class="waves-effect {{ request()->is('recommendation-scheduler*') ? 'active' : '' }}">
                                        <i class="bx bx-calendar"></i>
                                        <span key="t-rec-scheduler">Dynamic Scheduler</span>
                                    </a>
                                </li>
                                <li class="{{ request()->is('recommendation-roi*') ? 'mm-active' : '' }}">
                                    <a href="javascript: void(0);" class="waves-effect {{ request()->is('recommendation-roi*') ? 'active' : '' }}">
                                        <i class="bx bx-calculator"></i>
                                        <span key="t-rec-roi">ROI Calculator</span>
                                    </a>
                                </li>
                                <li class="{{ request()->is('recommendation-labor*') ? 'mm-active' : '' }}">
                                    <a href="javascript: void(0);" class="has-arrow waves-effect {{ request()->is('recommendation-labor*') ? 'mm-active' : '' }}">
                                        <i class="bx bx-hard-hat"></i>
                                        <span key="t-rec-labor">Labor Manager</span>
                                    </a>
                                    <ul class="sub-menu {{ request()->is('recommendation-labor*') ? 'mm-show' : '' }}" aria-expanded="{{ request()->is('recommendation-labor*') ? 'true' : 'false' }}">
                                        <li class="{{ request()->is('recommendation-labor-compliance*') ? 'mm-active' : '' }}">
                                            <a href="javascript: void(0);" class="waves-effect {{ request()->is('recommendation-labor-compliance*') ? 'active' : '' }}">
                                                <i class="bx bx-check-shield"></i>
                                                <span key="t-rec-labor-compliance">Compliance</span>
                                            </a>
                                        </li>
                                        <li class="{{ request()->is('recommendation-labor-reporter*') ? 'mm-active' : '' }}">
                                            <a href="javascript: void(0);" class="waves-effect {{ request()->is('recommendation-labor-reporter*') ? 'active' : '' }}">
                                                <i class="bx bx-file"></i>
                                                <span key="t-rec-labor-reporter">Reporter</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                                <li class="{{ request()->is('recommendation-analysis*') ? 'mm-active' : '' }}">
                                    <a href="javascript: void(0);" class="waves-effect {{ request()->is('recommendation-analysis*') ? 'active' : '' }}">
                                        <i class="bx bx-analyse"></i>
                                        <span key="t-rec-analysis">Analysis</span>
                                    </a>
                                </li>
                                <li class="{{ request()->is('recommendation-clients*') ? 'mm-active' : '' }}">
                                    <a href="javascript: void(0);" class="waves-effect {{ request()->is('recommendation-clients*') ? 'active' : '' }}">
                                        <i class="bx bx-group"></i>
                                        <span key="t-rec-clients">Clients</span>
                                    </a>
                                </li>
                                <li class="{{ request()->is('recommendation-settings*') ? 'mm-active' : '' }}">
                                    <a href="javascript: void(0);" class="waves-effect {{ request()->is('recommendation-settings*') ? 'active' : '' }}">
                                        <i class="bx bx-cog"></i>
                                        <span key="t-rec-settings">Settings</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="{{ request()->is('photo-analysis*') ? 'mm-active' : '' }}">
                            <a href="javascript: void(0);" class="has-arrow waves-effect {{ request()->is('photo-analysis*') ? 'mm-active' : '' }}">
                                <i class="bx bx-camera"></i>
                                <span key="t-photo-analysis">Photo Analysis</span>
                            </a>
                            <ul class="sub-menu {{ request()->is('photo-analysis*') ? 'mm-show' : '' }}" aria-expanded="{{ request()->is('photo-analysis*') ? 'true' : 'false' }}">
                                <li class="{{ request()->is('photo-analysis-analyze*') ? 'mm-active' : '' }}">
                                    <a href="javascript: void(0);" class="waves-effect {{ request()->is('photo-analysis-analyze*') ? 'active' : '' }}">
                                        <i class="bx bx-search-alt"></i>
                                        <span key="t-pa-analysis">Analysis</span>
                                    </a>
                                </li>
                                <li class="{{ request()->is('photo-analysis-clients*') ? 'mm-active' : '' }}">
                                    <a href="javascript: void(0);" class="waves-effect {{ request()->is('photo-analysis-clients*') ? 'active' : '' }}">
                                        <i class="bx bx-group"></i>
                                        <span key="t-pa-clients">Clients</span>
                                    </a>
                                </li>
                                <li class="{{ request()->is('photo-analysis-settings*') ? 'mm-active' : '' }}">
                                    <a href="javascript: void(0);" class="waves-effect {{ request()->is('photo-analysis-settings*') ? 'active' : '' }}">
                                        <i class="bx bx-cog"></i>
                                        <span key="t-pa-settings">Settings</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>

                <!-- Divider -->
                <li class="menu-title">─</li>

                <!-- E-commerce Navigation -->
                <li class="{{ request()->is('ecom-*') ? 'mm-active' : '' }}">
                    <a href="javascript: void(0);" class="has-arrow waves-effect {{ request()->is('ecom-*') ? 'mm-active' : '' }}">
                        <i class="bx bx-shopping-bag"></i>
                        <span key="t-ecommerce">E-commerce</span>
                    </a>
                    <ul class="sub-menu {{ request()->is('ecom-*') ? 'mm-show' : '' }}" aria-expanded="{{ request()->is('ecom-*') ? 'true' : 'false' }}">
                        <li class="{{ request()->is('ecom-stores*') ? 'mm-active' : '' }}">
                            <a href="{{ route('ecom-stores') }}" class="waves-effect {{ request()->is('ecom-stores*') ? 'active' : '' }}">
                                <i class="bx bx-store"></i>
                                <span key="t-stores">Stores</span>
                            </a>
                        </li>
                        <li class="{{ request()->is('ecom-clients') ? 'mm-active' : '' }}">
                            <a href="{{ route('ecom-clients') }}" class="waves-effect {{ request()->is('ecom-clients') ? 'active' : '' }}">
                                <i class="bx bx-user-circle"></i>
                                <span key="t-clients">All Clients</span>
                            </a>
                        </li>
                        <li class="{{ request()->is('ecom-client-shippings*') ? 'mm-active' : '' }}">
                            <a href="{{ route('ecom-client-shippings') }}" class="waves-effect {{ request()->is('ecom-client-shippings*') ? 'active' : '' }}">
                                <i class="bx bx-map-pin"></i>
                                <span key="t-client-shippings">Client Shippings</span>
                            </a>
                        </li>
                        <li class="{{ request()->is('ecom-refunds*') ? 'mm-active' : '' }}">
                            <a href="{{ route('ecom-refunds') }}" class="waves-effect {{ request()->is('ecom-refunds*') ? 'active' : '' }}">
                                <i class="bx bx-undo"></i>
                                <span key="t-refunds">Refunds</span>
                            </a>
                        </li>
                        <li class="{{ request()->is('ecom-products*') ? 'mm-active' : '' }}">
                            <a href="{{ route('ecom-products') }}" class="waves-effect {{ request()->is('ecom-products*') ? 'active' : '' }}">
                                <i class="bx bx-package"></i>
                                <span key="t-products">Products</span>
                            </a>
                        </li>
                        <li class="{{ request()->is('ecom-packages*') ? 'mm-active' : '' }}">
                            <a href="{{ route('ecom-packages') }}" class="waves-effect {{ request()->is('ecom-packages*') ? 'active' : '' }}">
                                <i class="bx bx-box"></i>
                                <span key="t-packages">Packages</span>
                            </a>
                        </li>
                        <li class="{{ request()->is('ecom-orders*') ? 'mm-active' : '' }}">
                            <a href="{{ route('ecom-orders') }}" class="waves-effect {{ request()->is('ecom-orders*') ? 'active' : '' }}">
                                <i class="bx bx-cart"></i>
                                <span key="t-orders">Orders</span>
                            </a>
                        </li>
                        <li class="{{ request()->is('ecom-shipping*') ? 'mm-active' : '' }}">
                            <a href="{{ route('ecom-shipping') }}" class="waves-effect {{ request()->is('ecom-shipping*') ? 'active' : '' }}">
                                <i class="bx bx-car"></i>
                                <span key="t-shipping">Shipping</span>
                            </a>
                        </li>
                        <li class="{{ request()->is('ecom-discounts*') ? 'mm-active' : '' }}">
                            <a href="{{ route('ecom-discounts') }}" class="waves-effect {{ request()->is('ecom-discounts*') ? 'active' : '' }}">
                                <i class="bx bx-tag"></i>
                                <span key="t-discounts">Discounts</span>
                            </a>
                        </li>
                        <li class="{{ request()->is('ecom-affiliates*') ? 'mm-active' : '' }}">
                            <a href="{{ route('ecom-affiliates') }}" class="waves-effect {{ request()->is('ecom-affiliates*') ? 'active' : '' }}">
                                <i class="bx bx-group"></i>
                                <span key="t-affiliates">Affiliates</span>
                            </a>
                        </li>
                        <li class="{{ request()->is('ecom-triggers*') ? 'mm-active' : '' }}">
                            <a href="{{ route('ecom-triggers') }}" class="waves-effect {{ request()->is('ecom-triggers*') ? 'active' : '' }}">
                                <i class="bx bx-key"></i>
                                <span key="t-triggers">Trigger Flows</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Divider -->
                <li class="menu-title">─</li>

                <!-- CRM Navigation -->
                <li class="{{ request()->is('crm-*') ? 'mm-active' : '' }}">
                    <a href="javascript: void(0);" class="has-arrow waves-effect {{ request()->is('crm-*') ? 'mm-active' : '' }}">
                        <i class="bx bx-briefcase"></i>
                        <span key="t-crm">CRM</span>
                    </a>
                    <ul class="sub-menu {{ request()->is('crm-*') ? 'mm-show' : '' }}" aria-expanded="{{ request()->is('crm-*') ? 'true' : 'false' }}">
                        <li class="{{ request()->is('crm-leads*') ? 'mm-active' : '' }}">
                            <a href="{{ route('crm-leads') }}" class="waves-effect {{ request()->is('crm-leads*') ? 'active' : '' }}">
                                <i class="bx bx-user-plus"></i>
                                <span key="t-crm-leads">Leads</span>
                            </a>
                        </li>
                        <li class="{{ request()->is('crm-business-contacts*') ? 'mm-active' : '' }}">
                            <a href="{{ route('crm-business-contacts') }}" class="waves-effect {{ request()->is('crm-business-contacts*') ? 'active' : '' }}">
                                <i class="bx bx-id-card"></i>
                                <span key="t-crm-business-contacts">Business Contacts</span>
                            </a>
                        </li>
                        <li class="{{ request()->is('crm-forms*') ? 'mm-active' : '' }}">
                            <a href="{{ route('crm-forms') }}" class="waves-effect {{ request()->is('crm-forms*') ? 'active' : '' }}">
                                <i class="bx bx-list-ul"></i>
                                <span key="t-crm-forms">Forms</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Divider -->
                <li class="menu-title">─</li>

                <!-- Reports Navigation -->
                <li class="{{ request()->is('ecom-reports*') ? 'mm-active' : '' }}">
                    <a href="javascript: void(0);" class="has-arrow waves-effect {{ request()->is('ecom-reports*') ? 'mm-active' : '' }}">
                        <i class="bx bx-line-chart"></i>
                        <span key="t-reports">Reports</span>
                    </a>
                    <ul class="sub-menu {{ request()->is('ecom-reports*') ? 'mm-show' : '' }}" aria-expanded="{{ request()->is('ecom-reports*') ? 'true' : 'false' }}">
                        <li class="{{ request()->is('ecom-reports-sales*') ? 'mm-active' : '' }}">
                            <a href="{{ route('ecom-reports.sales') }}" class="waves-effect {{ request()->is('ecom-reports-sales*') ? 'active' : '' }}">
                                <i class="bx bx-receipt"></i>
                                <span key="t-sales-report">Sales Report</span>
                            </a>
                        </li>
                        <li class="{{ request()->is('ecom-reports-heatmap*') ? 'mm-active' : '' }}">
                            <a href="{{ route('ecom-reports.heatmap') }}" class="waves-effect {{ request()->is('ecom-reports-heatmap*') ? 'active' : '' }}">
                                <i class="bx bx-map-alt"></i>
                                <span key="t-heatmap">Heatmap</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Divider -->
                <li class="menu-title">─</li>

                <!-- APIs Navigation -->
                <li class="{{ request()->is('api-docs*') ? 'mm-active' : '' }}">
                    <a href="javascript: void(0);" class="has-arrow waves-effect {{ request()->is('api-docs*') ? 'mm-active' : '' }}">
                        <i class="bx bx-code-alt"></i>
                        <span key="t-apis">APIs</span>
                    </a>
                    <ul class="sub-menu {{ request()->is('api-docs*') ? 'mm-show' : '' }}" aria-expanded="{{ request()->is('api-docs*') ? 'true' : 'false' }}">
                        <li class="{{ request()->is('api-docs-leads*') ? 'mm-active' : '' }}">
                            <a href="{{ route('api-docs.leads') }}" class="waves-effect {{ request()->is('api-docs-leads*') ? 'active' : '' }}">
                                <i class="bx bx-user-plus"></i>
                                <span key="t-api-leads">Leads</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Divider -->
                <li class="menu-title">─</li>

                <!-- Admin Users Navigation -->
                <li class="{{ request()->is('users*') ? 'mm-active' : '' }}">
                    <a href="{{ route('users.index') }}" class="waves-effect {{ request()->is('users*') ? 'active' : '' }}">
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
