<div class="main-nav">
     <!-- Sidebar Logo -->
     <div class="logo-box">
          <a href="/" class="logo-dark">
               <?= component('ui.icon', ['icon' => 'square-binary', 'class' => 'fa-2x text-primary']) ?>
               <span class="fs-30 fw-bold logo-lg text-dark"> <?= app_name() ?></span>
          </a>

          <a href="/" class="logo-light">
               <?= component('ui.icon', ['icon' => 'binary', 'class' => 'fa-2x text-primary']) ?>
               <span class="fs-30 fw-bold logo-lg"> <?= app_name() ?></span>
          </a>
     </div>

     <!-- Menu Toggle Button (sm-hover) -->
     <button type="button" class="button-sm-hover" aria-label="Show Full Sidebar">
          <iconify-icon icon="solar:double-alt-arrow-right-bold-duotone" class="button-sm-hover-icon"></iconify-icon>
     </button>

     <div class="scrollbar" data-simplebar>
          <ul class="navbar-nav" id="navbar-nav">

               <li class="nav-item">
                    <a class="nav-link" href="<?= route('home') ?>">
                         <span class="nav-icon">
                              <?= component('ui.icon', ['icon' => 'grid-2']) ?>
                         </span>
                         <span class="nav-text">Home</span>
                    </a>
               </li>

               <li class="nav-item">
                    <a class="nav-link" href="<?php //route('map') ?>">
                         <span class="nav-icon">
                              <?= component('ui.icon', ['icon' => 'map']) ?>
                         </span>
                         <span class="nav-text"> Map </span>
                    </a>
               </li>

               <li class="nav-item">
                    <a class="nav-link menu-arrow" href="#sidebarApplications" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarProducts">
                         <span class="nav-icon">
                              <?= component('ui.icon', ['icon' => 'browser']) ?>
                         </span>
                         <span class="nav-text"> Applications </span>
                    </a>
                    <div class="collapse" id="sidebarApplications">
                         <ul class="nav sub-navbar-nav">
                              <li class="sub-nav-item">
                                   <a class="sub-nav-link" href="<?= route('applications.create') ?>"> Create </a>
                              </li>
                              <li class="sub-nav-item">
                                   <a class="sub-nav-link" href="<?= route('applications.migrate') ?>"> Migrate </a>
                              </li>
                              <li class="sub-nav-item">
                                   <a class="sub-nav-link" href="<?php //route('application.list') ?>"> All Lists </a>
                              </li>
                         </ul>
                    </div>
               </li>

               <li class="nav-item">
                    <a class="nav-link menu-arrow" href="#sidebarReport" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarCategory">
                         <span class="nav-icon">
                              <?= component('ui.icon', ['icon' => 'chart-mixed']) ?>
                         </span>
                         <span class="nav-text"> Reports </span>
                    </a>
                    <div class="collapse" id="sidebarReport">
                         <ul class="nav sub-navbar-nav">
                              <li class="sub-nav-item">
                                   <a class="sub-nav-link" href="<?php //route('report.progress') ?>"> Progress </a>
                              </li>                         
                              <li class="sub-nav-item">
                                   <a class="sub-nav-link" href="<?php //route('report.completed') ?>"> Completed </a>
                              </li>
                              <li class="sub-nav-item">
                                   <a class="sub-nav-link" href="<?php //route('report.corrupted') ?>"> Corrupted </a>
                              </li>
                         </ul>
                    </div>
               </li>


          </ul>
     </div>
</div>