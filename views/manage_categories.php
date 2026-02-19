<?php
include("../includes/auth_check.php");
include("../includes/header.php");
require_once("../config/connect.php");
require_once("../includes/common.php");

$base_path = "complaint_categories/"; 

$files = [];
if (is_dir($base_path)) {
    $dir_files = scandir($base_path);
    foreach ($dir_files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'txt') {
            $key = pathinfo($file, PATHINFO_FILENAME);
            $files[$key] = $file;
        }
    }
}
ksort($files);
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>

<style>
    /* Prevent overall horizontal scroll */
    html, body {
        overflow-x: hidden;
        width: 100%;
    }

    .category-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(15px);
        border-radius: 20px;
        border: 1px solid rgba(255,255,255,0.4);
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        margin-bottom: 25px;
        transition: transform 0.3s ease;
        height: 400px; 
        display: flex;
        flex-direction: column;
        width: 100%; /* Ensure card doesn't exceed container */
    }
    .category-card:hover { transform: translateY(-5px); }
    .card-header-premium {
        background: linear-gradient(135deg, #6366f1, #a855f7);
        color: white; padding: 15px 20px;
        border-radius: 20px 20px 0 0;
        font-weight: 700; display: flex;
        justify-content: space-between; align-items: center;
    }
    .card-body-scrollable { flex-grow: 1; overflow-y: auto; padding: 0 !important; }
    
    .card-body-scrollable::-webkit-scrollbar { width: 6px; }
    .card-body-scrollable::-webkit-scrollbar-thumb { background: #a855f7; border-radius: 10px; }

    .list-item-custom {
        display: flex; justify-content: space-between; align-items: center;
        padding: 12px 15px; /* Reduced horizontal padding for narrow screens */
        border-bottom: 1px solid rgba(0,0,0,0.05); font-weight: 500;
        width: 100%;
    }
    .btn-glass-add { background: #22c55e; color: white; border: none; padding: 5px 12px; border-radius: 10px; font-size: 0.8rem; }
    
    .create-cat-container {
        display: flex; gap: 10px; background: #fff; padding: 8px 15px;
        border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        max-width: 100%; /* Constraint for mobile */
    }
    .create-cat-input {
        border: 1px solid #e0e7ff; border-radius: 10px; padding: 5px 15px; outline: none; 
        width: 100%; /* Changed from fixed 220px to 100% */
        flex: 1;
    }
    .btn-create-category {
        background: linear-gradient(135deg, #0ea5e9, #2563eb);
        color: white; border: none; padding: 8px 18px;
        border-radius: 10px; font-weight: 600; transition: all 0.3s ease;
        white-space: nowrap;
    }
    .premium-swal-popup { border-radius: 25px !important; border: 1px solid rgba(255,255,255,0.4) !important; width: 90% !important; }

    /* --- MOBILE RESPONSIVE FIXES --- */
    @media (max-width: 768px) {
        .container {
            padding-left: 10px !important;
            padding-right: 10px !important;
        }
        .header-section {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 15px;
            width: 100%;
        }
        .create-cat-container {
            width: 100%;
            padding: 8px;
            gap: 5px;
        }
        .create-cat-input {
            width: 100%;
            min-width: 0; /* Allows input to shrink below its default size */
        }
        .btn-create-category {
            width: auto;
            padding: 8px 12px;
            font-size: 0.9rem;
        }
        .category-card {
            height: 350px; 
            margin-bottom: 15px;
        }
        .card-header-premium {
            padding: 12px 15px;
        }
        .card-header-premium span {
            font-size: 0.9rem;
            max-width: 50%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    }

    /* Extra small screens fix (e.g. iPhone 5/SE or narrow responsive views) */
    @media (max-width: 350px) {
        .btn-create-category {
            padding: 8px 10px;
            font-size: 0.8rem;
        }
        .card-header-premium span {
            font-size: 0.8rem;
        }
        .btn-glass-add {
            padding: 4px 8px;
            font-size: 0.7rem;
        }
    }
</style>

<main class="container py-4">
    <div class="row m-0">
        <div class="col-md-3 p-0"><?php include("../includes/menu.php"); ?></div>
        
        <div class="col-md-9 p-0 p-md-3">
            <div class="d-flex justify-content-between align-items-center mb-4 header-section">
                <h4 class="mb-0"><i class="fa-solid fa-sliders text-primary me-2"></i> Category Management</h4>
                
                <form action="category_handler.php" method="POST" class="create-cat-container">
                    <input type="hidden" name="action" value="create_file">
                    <input type="text" name="new_file_name" class="create-cat-input" placeholder="Category Name" required>
                    <button type="submit" class="btn-create-category">
                        <i class="fa-solid fa-plus"></i> Create
                    </button>
                </form>
            </div>
            
            <div class="row m-0">
                <?php foreach ($files as $key => $filename): 
                    $filePath = $base_path . $filename;
                    $options = file_exists($filePath) ? file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
                ?>
                <div class="col-12 col-md-6 p-1 p-md-2">
                    <div class="category-card">
                        <div class="card-header-premium">
                            <span><i class="fa-solid fa-folder-open me-2"></i> <?= strtoupper($key) ?></span>
                            <div class="d-flex gap-1 gap-md-2">
                                <button class="btn btn-sm btn-danger border-0" style="border-radius: 8px; padding: 4px 8px;" onclick="deleteCategoryFile('<?= $key ?>')">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                                <button class="btn-glass-add" onclick="openAddModal('<?= $key ?>')">
                                    <i class="fa-solid fa-plus"></i> Add
                                </button>
                            </div>
                        </div>
                        <div class="card-body card-body-scrollable">
                            <?php if(empty($options)): ?>
                                <p class="p-3 text-muted">No options found.</p>
                            <?php else: ?>
                                <?php foreach($options as $index => $opt): 
                                    $displayLabel = preg_replace('/\s*-\s*\d+$/', '', trim($opt));
                                ?>
                                    <div class="list-item-custom">
                                        <span class="text-truncate me-2" title="<?= htmlspecialchars($displayLabel) ?>"><?= htmlspecialchars($displayLabel) ?></span>
                                        <div style="white-space: nowrap;">
                                            <button class="btn btn-sm btn-outline-primary border-0 px-2" 
                                                    onclick="editOption('<?= $key ?>', '<?= $index ?>', '<?= addslashes($displayLabel) ?>')">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger border-0 px-2" onclick="deleteOption('<?= $key ?>', '<?= $index ?>')">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="manageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mx-auto" style="max-width: 90%;">
        <div class="modal-content" style="border-radius: 20px;">
            <form action="category_handler.php" method="POST">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="modalTitle">Manage Option</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="category_key" id="category_key">
                    <input type="hidden" name="option_index" id="option_index">
                    <input type="hidden" name="action" id="form_action">
                    <div class="form-group">
                        <label class="mb-2 fw-bold">Option Name</label>
                        <input type="text" name="option_value" id="option_value" class="form-control" required style="border-radius: 12px;">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-primary w-100" style="border-radius: 12px;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// (Keep all original JavaScript logic for SweetAlert and CRUD functions)
const urlParams = new URLSearchParams(window.location.search);
const status = urlParams.get('status');
if (status) {
    let config = { icon: 'success', timer: 3000, showConfirmButton: false, customClass: { popup: 'premium-swal-popup' }};
    switch(status) {
        case 'added': config.title = "Added!"; config.text = "New option added."; break;
        case 'updated': config.title = "Updated!"; config.text = "Option modified successfully."; break;
        case 'deleted': config.title = "Deleted!"; config.text = "Option removed."; config.icon = 'info'; break;
        case 'file_created': config.title = "Created!"; config.text = "Category file created."; break;
        case 'file_deleted': config.title = "Category Removed!"; config.icon = 'warning'; break;
        case 'error_exists': config.title = "Error"; config.text = "Category already exists."; config.icon = 'error'; break;
    }
    if (config.title) Swal.fire(config);
    window.history.replaceState({}, document.title, window.location.pathname);
}

function openAddModal(key) {
    document.getElementById('modalTitle').innerText = "Add to " + key.toUpperCase();
    document.getElementById('category_key').value = key;
    document.getElementById('form_action').value = "add";
    document.getElementById('option_value').value = "";
    new bootstrap.Modal(document.getElementById('manageModal')).show();
}

function editOption(key, index, val) {
    document.getElementById('modalTitle').innerText = "Edit Option";
    document.getElementById('category_key').value = key;
    document.getElementById('option_index').value = index;
    document.getElementById('option_value').value = val;
    document.getElementById('form_action').value = "edit";
    new bootstrap.Modal(document.getElementById('manageModal')).show();
}

function deleteOption(key, index) {
    Swal.fire({
        title: 'Delete Option?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it'
    }).then((result) => {
        if (result.isConfirmed) submitHiddenForm({ action: 'delete', category_key: key, option_index: index });
    });
}

function deleteCategoryFile(key) {
    Swal.fire({
        title: 'Delete Category File?',
        text: "This removes the entire " + key.toUpperCase() + " category.",
        icon: 'error',
        showCancelButton: true,
        confirmButtonText: 'Confirm Delete'
    }).then((result) => {
        if (result.isConfirmed) submitHiddenForm({ action: 'delete_file', category_key: key });
    });
}

function submitHiddenForm(data) {
    const form = document.createElement('form');
    form.method = 'POST'; form.action = 'category_handler.php';
    for (const f in data) {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = f; input.value = data[f];
        form.appendChild(input);
    }
    document.body.appendChild(form); form.submit();
}
</script>
<?php include("../includes/footer.php"); ?>