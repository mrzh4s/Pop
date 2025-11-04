<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex gap-2 align-items-center">
            <a href="<?= route('applications.migrate') ?>" class="btn btn-primary">
                <?= component('ui.icon', ['icon' => 'arrow-left']) ?>
            </a>
            <div>
                <h4 class="mb-0">Entry #<?= $entryId ?></h4>
                <p class="text-muted mb-0">I-mohon Application</p>
            </div>

        </div>
    </div>
    <?php
    if (isset($_GET['debug']) && $_GET['debug'] === '1') {
        dd($data);
    }
    ?>
</div>
<div class="row">
    <!-- Main Content (2 columns) -->
    <div class="col-lg-8">
        <div class="card mb-1">
            <div class="card-body p-1">
                <ul class="nav nav-pills nav-justified">
                    <li class="nav-item">
                        <a href="#projects" data-bs-toggle="tab" aria-expanded="false" class="nav-link active">
                            <span class="d-block d-sm-none"><?= component('ui.icon', ['icon' => 'square-kanban']) ?></span>
                            <span class="d-none d-sm-block">Project Details</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#contacts" data-bs-toggle="tab" aria-expanded="true" class="nav-link">
                            <span class="d-block d-sm-none"><?= component('ui.icon', ['icon' => 'road']) ?></span>
                            <span class="d-none d-sm-block">Road Details</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#contacts" data-bs-toggle="tab" aria-expanded="true" class="nav-link">
                            <span class="d-block d-sm-none"><?= component('ui.icon', ['icon' => 'address-book']) ?></span>
                            <span class="d-none d-sm-block">Contact Details</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#attachments" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                            <span class="d-block d-sm-none"><?= component('ui.icon', ['icon' => 'paperclip']) ?></span>
                            <span class="d-none d-sm-block">Attachment Details</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="tab-content text-muted">
            <div class="tab-pane show active" id="projects">
                <!-- Entry Information Card -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between">
                        <h5 class="card-title mb-0">Project Details</h5>
                        <?= component('ui.icon', ['icon' => 'info-circle', 'class' => 'fs-18 text-orange me-1', 'attributes' => ['data-bs-toggle' => 'tooltip', 'data-bs-title' => 'Orange circle info indicates as existing data in database KITER that already migrated']]) ?>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-md-12">
                                <label class="form-label text-muted">Title</label>
                                <textarea class="form-control" name="title" rows="5"><?= $data->imohon->entry->{'4'} ?? '' ?></textarea>
                            </div>
                            <div class="d-flex pt-1">
                                <?= component('ui.icon', ['icon' => 'info-circle', 'class' => 'text-orange me-1']) ?>
                                <span class="fs-12 text-orange"><?= $data->kiter->project_title ?></span>
                            </div>

                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label class="form-label text-muted">Reference No</label>
                                <input class="form-control" name="reference_no" value="<?= $data->imohon->entry->{'45'} ?? '' ?>" />
                                <div class="d-flex pt-1">
                                    <?= component('ui.icon', ['icon' => 'info-circle', 'class' => 'text-orange me-1']) ?>
                                    <span class="fs-12 text-orange"><?= $data->kiter->reference_no ?></span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label text-muted">Created At</label>
                                <input class="form-control" data-flatpickr data-enable-time data-date-format="Y-m-d H:i" name="created_at" value="<?= $data->imohon->entry->date_created ?>" />
                                <div class="d-flex pt-1">
                                    <?= component('ui.icon', ['icon' => 'info-circle', 'class' => 'text-orange me-1']) ?>
                                    <span class="fs-12 text-orange"><?= $data->kiter->created_at ?? 'Tiada' ?></span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label text-muted">District</label>
                                <?php
                                $district = [];

                                // Check for keys from 129.1 to 129.9
                                for ($i = 1; $i <= 9; $i++) {
                                    $key = "129.{$i}";
                                    if (!empty($data->imohon->entry->{$key})) {
                                        $district[] = $data->imohon->entry->{$key};
                                    }
                                }

                                $district = implode(', ', $district);
                                ?>
                                <input class="form-control" name="district" value="<?= $district ?? '' ?>" />
                                <div class="d-flex pt-1">
                                    <?= component('ui.icon', ['icon' => 'info-circle', 'class' => 'text-orange me-1']) ?>
                                    <span class="fs-12 text-orange"><?= $data->kiter->district_name ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label class="form-label text-muted">Project Length</label>
                                <input class="form-control" name="length" value="<?= $data->imohon->entry->{'102'} ?? 0 ?>" />
                                <div class="d-flex pt-1">
                                    <?= component('ui.icon', ['icon' => 'info-circle', 'class' => 'text-orange me-1']) ?>
                                    <span class="fs-10 text-orange"><?= $data->kiter->application_length ?? 'Tiada' ?></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted">Project Costs</label>
                                <input class="form-control" name="costs" value="<?= 0 ?>" />
                                <div class="d-flex pt-1">
                                    <?= component('ui.icon', ['icon' => 'info-circle', 'class' => 'text-orange me-1']) ?>
                                    <span class="fs-12 text-orange"><?= $data->kiter->project_costs ?? 'Tiada' ?></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted">Work Cateogry</label>
                                <select class="form-select" data-choices name="work_category">
                                    <option value="KT">Planned Work</option>
                                    <option value="KP">Relocation Work</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between">
                        <h5 class="card-title mb-0">Quotation Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label class="form-label text-muted">Submit At</label>
                                <input class="form-control" name="quotation_submit_at" value="<?= $data->imohon->quotation->{'3'} ?? '' ?>" />
                                <div class="d-flex pt-1">
                                    <?= component('ui.icon', ['icon' => 'info-circle', 'class' => 'text-orange me-1']) ?>
                                    <span class="fs-12 text-orange"><?= $data->kiter->quotation_submit_at ?? 'Tiada' ?></span>
                                </div>

                                <label class="form-label text-muted">Approved At</label>
                                <input class="form-control" name="quotation_attachment" value="" />
                                <div class="d-flex pt-1">
                                    <?= component('ui.icon', ['icon' => 'info-circle', 'class' => 'text-orange me-1']) ?>
                                    <span class="fs-12 text-orange"><?= $data->kiter->quotation_approved_at ?? 'Tiada' ?></span>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="mb-2">
                                        <label class="form-label text-muted">Notes</label>
                                        <textarea class="form-control" name="quotation_notes" rows="5"><?= $data->imohon->quotation->{'7'} ?? '' ?></textarea>
                                        <div class="d-flex pt-1">
                                            <?= component('ui.icon', ['icon' => 'info-circle', 'class' => 'text-orange me-1']) ?>
                                            <span class="fs-12 text-orange"><?= 'Tiada' ?></span>
                                        </div>
                                    </div>

                                    <!-- <div class="col-6">
                                        <?php
                                        // $url = explode('/',$data->imohon->quotation->{'1'}['0']);
                                        // $url = end($url);
                                        // $url = str_replace('_', ' ', $url);
                                        // if (strlen($url) > 20) {
                                        //     $url = substr($url, 0, 17) . '...';
                                        // }
                                        ?>
                                        <label class="form-label text-muted">Quotation</label>

                                        <button type="button" data-bs-toggle="modal" data-bs-target="#quotation" class="btn btn-outline-dark border border-secondary-subtle d-flex align-items-center justify-content-center gap-1 w-100"> 
                                            <?php //component('ui.icon', ['icon' => 'paperclip', 'class' => 'fs-14 align-middle']) 
                                            ?>
                                            <?php //$url 
                                            ?>
                                        </button>

                                        <div class="modal fade" id="quotation" tabindex="-1" aria-labelledby="quotation" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered" style="max-width: 50%;">
                                                <div class="modal-content">
                                                    <div class="modal-body p-0">
                                                       <iframe src="<?php //$data->imohon->quotation->{'1'}['0'] 
                                                                    ?>" width="100%" height="750px"></iframe>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <input class="form-control" name="quotation_attachment" value="<?php //$data->imohon->quotation->{'1'}['0'] ?? '' 
                                                                                                        ?>" hidden />
                                    </div>
                                    <div class="col-6">
                                        <?php
                                        //$url = explode('/',$data->imohon->entry->{'49'});
                                        //$url = end($url);
                                        //$url = str_replace('_', ' ', $url);
                                        //if (strlen($url) > 20) {
                                        //$url = substr($url, 0, 17) . '...';
                                        //}
                                        ?>
                                        <label class="form-label text-muted">Quotation Approved</label>

                                        <button type="button" data-bs-toggle="modal" data-bs-target="#quotation_approved" class="btn btn-outline-dark border border-secondary-subtle d-flex align-items-center justify-content-center gap-1 w-100"> 
                                            <?php //component('ui.icon', ['icon' => 'paperclip', 'class' => 'fs-14 align-middle']) 
                                            ?>
                                            <?php //$url 
                                            ?>
                                        </button>

                                        <div class="modal fade" id="quotation_approved" tabindex="-1" aria-labelledby="quotation_approved" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered" style="max-width: 75%;">
                                                <div class="modal-content">
                                                    <div class="modal-body p-0">
                                                       <iframe src="<?php //$data->imohon->entry->{'49'} 
                                                                    ?>" width="100%" height="750px"></iframe>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <input class="form-control" name="quotation_attachment" value="<?php //$data->imohon->entry->{'49'} ?? '' 
                                                                                                        ?>" hidden />
                                    </div> -->
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between">
                        <h5 class="card-title mb-0">Authority Details</h5>
                    </div>
                    <div class="card-body p-0">
    <table class="table table-responsive table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th>Authority</th>
                <th>Letter Date</th>
                <th>Received Date</th>
                <th>Approval Date</th>
                <th>Deposit (RM)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody class="table-group-divider">
            <?php 
            $jsonArray = []; // Create an array to hold all records
            ?>
            <?php foreach ($data->imohon->authority as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row->{1}) ?></td>
                    <td><?= !empty($row->{2}) ? date('d/m/Y', strtotime($row->{2})) : '-' ?></td>
                    <td><?= !empty($row->{3}) ? date('d/m/Y', strtotime($row->{3})) : '-' ?></td>
                    <td><?= !empty($row->{8}) ? date('d/m/Y', strtotime($row->{8})) : '-' ?></td>
                    <td><?= !empty($row->{7}) ? number_format((int) $row->{7}, 0) : '0' ?></td>
                    <td>
                        <span class="badge bg-primary">
                            <?= htmlspecialchars($row->current_status); ?>
                        </span>
                    </td>
                </tr>
                <?php 
                // Add each row as a separate object in the array
                $jsonArray[] = [
                    'authority' => $row->{1},
                    'letter_date' => !empty($row->{2}) ? date('Y-m-d', strtotime($row->{2})) : '',
                    'received_date' => !empty($row->{3}) ? date('Y-m-d', strtotime($row->{3})) : '',
                    'approval_date' => !empty($row->{8}) ? date('Y-m-d', strtotime($row->{8})) : '',
                    'deposit' => !empty($row->{7}) ? (int) $row->{7} : 0,
                    'status' => $row->current_status
                ];
                ?>
            <?php endforeach ?>
            <tr>
                <td colspan="6">
                    <textarea class="form-control" rows="15" name="authority"><?= json_encode($jsonArray, JSON_PRETTY_PRINT) ?></textarea>
                </td>
            </tr>
        </tbody>
    </table>
</div>
                </div>
            </div>

            <div class="tab-pane" id="contacts">
                ..
            </div>

            <div class="tab-pane" id="contacts">
                ..
            </div>
            <div class="tab-pane" id="attachments">
                ...
            </div>
        </div>
    </div>


    <!-- Sidebar (1 column) -->
    <div class="col-lg-4">

        <!-- Summary Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Action</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted">Project Status</label>
                    <select class="form-select" data-choices name="choices-single-default" name="status">
                        <option value="0">Pending</option>
                        <option value="1">Approved</option>
                        <option value="2">Rejected</option>
                    </select>
                </div>
                <hr>
                <div>
                    <label class="form-label text-muted">Task Assignment</label>
                    <select class="form-select" data-choices name="choices-single-default" name="task_assignment">
                        <option value="0">Pending</option>
                        <option value="1">Approved</option>
                        <option value="2">Rejected</option>
                    </select>
                </div>
            </div>
            <div class="card-footer bg-body-tertiary text-end">
                <a href="<?= route('applications.migrate') ?>" class="btn btn-soft-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
</div>