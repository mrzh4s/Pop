<header class="topbar">
     <div class="container-fluid">
          <div class="navbar-header">
               <div class="d-flex align-items-center">
                    <!-- Menu Toggle Button -->
                    <div class="topbar-item">
                         <button type="button" class="button-toggle-menu me-2">
                              <iconify-icon icon="solar:hamburger-menu-broken" class="fs-24 align-middle"></iconify-icon>
                         </button>
                    </div>

                    <!-- Menu Toggle Button -->
                    <div class="topbar-item">
                         <h4 class="fw-bold topbar-button pe-none text-uppercase mb-0"><?= slot('page-title') ?></h4>
                    </div>
               </div>

               <div class="d-flex align-items-center">
                    <!-- App Search-->
                    <form class="app-search d-none d-md-block ms-2">
                         <div class="position-relative">
                              <input type="search" class="form-control" placeholder="Search..." autocomplete="off" value="">
                              <?= component('ui/icon', ['icon' => 'search', 'class' => 'search-widget-icon']) ?>
                         </div>
                    </form>

                    <!-- Theme Color (Light/Dark) -->
                    <div class="topbar-item">
                         <button type="button" class="topbar-button" id="light-dark-mode">
                              <?= component('ui/icon', ['icon' => 'moon', 'class' => 'fs-20 align-middle']) ?>
                         </button>
                    </div>

                    <!-- User -->
                    <div class="dropdown topbar-item">
                         <a type="button" class="topbar-button" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                              <span class="d-flex align-items-center">
                                   <?= component('ui/icon', ['icon' => 'user-circle', 'class' => 'fs-24 align-middle']) ?>
                              </span>
                         </a>
                         <div class="dropdown-menu dropdown-menu-end">
                              <!-- item-->
                              <h6 class="dropdown-header">Welcome <?= session('user.name') ?> !</h6>
                              <script>
                                   async function system_logout() {
                                        const response = await fetch('/api/auth/logout', {
                                             method: 'POST',
                                        });

                                        if (response.ok) {
                                             const result = await response.json();
                                             showToast('success', 'Logout Success', 'You have been logged out successfully.');
                                             setTimeout(() => {
                                                  window.location.href = '/';
                                             }, 2500);
                                        } else {
                                             const error = await response.json();
                                             showToast('error', 'Logout Failed', 'An error occurred during logout.');
                                        }
                                   }     
                              </script>

                              <a class="dropdown-item text-danger" href="javscript:void(0)" onclick="system_logout()">
                                   <?= component('ui.icon', ['icon' => 'arrow-left-from-bracket', 'class' => 'fs-16 align-middle me-1']) ?>
                                   <span class="align-middle">Logout</span>
                              </a>
                         </div>
                    </div>
               </div>
          </div>
     </div>
</header>