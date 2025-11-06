<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit(); }
require 'db_config.php';

$workerId = isset($_GET['worker_id']) ? (int)$_GET['worker_id'] : null;
$workerName = '';
if ($workerId) {
  $res = $conn->query("SELECT name FROM workers WHERE id=$workerId");
  if ($res && $res->num_rows) $workerName = $res->fetch_assoc()['name'];
}

// preload groups & parts
$groups = [];
$resG = $conn->query("SELECT id, group_name, image_path FROM motor_groups ORDER BY id");
while ($g = $resG->fetch_assoc()) $groups[] = $g;

$partsByGroup = [];
$resP = $conn->query("SELECT id, name, cost, group_id FROM motor_parts ORDER BY name");
while ($p = $resP->fetch_assoc()) {
  $gid = (int)$p['group_id'];
  $partsByGroup[$gid][] = $p;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Add Work - Krishna Pump</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#f4f5fa; overflow-x:hidden; }
    .group-img{cursor:pointer;width:70px;height:70px;margin:4px;object-fit:contain;border-radius:8px;border:2px solid transparent;transition:.2s}
    .group-img:hover{border-color:#0d6efd;transform:scale(1.03)}
    .part-card{border:1px solid #dee2e6;border-radius:6px;padding:6px;text-align:center;font-weight:bold;font-size:0.9rem;cursor:pointer;transition:.2s}
    .part-card:hover{background:#f1f1f1;}
    .pill{border:1px solid #e5e7eb;border-radius:999px;padding:.25rem .6rem;font-size:.85rem;margin:.15rem;display:inline-flex;gap:.4rem;align-items:center;background:#fff;}
    .pill .x{cursor:pointer;font-weight:bold}
    .worker-bar{background:#f8f9fa;padding:8px 15px;margin-bottom:15px;border:1px solid #dee2e6;border-radius:6px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;}
    .bold-input{font-weight:bold;}

    /* ✅ Floating modal that doesn’t block background */
    .floating-modal {
      position: fixed;
      top: 50%; left: 50%;
      transform: translate(-50%, -50%);
      z-index: 1080;
      pointer-events: none;
    }
    .floating-modal .modal-content {
      pointer-events: auto;
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.3);
      max-height: 80vh;
      overflow-y: auto;
    }
  </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main" style="margin-left:250px;padding:20px;max-width:1200px;">

  <!-- 🧍 Worker Info Bar -->
  <div id="workerBar" class="worker-bar <?= $workerId ? '' : 'd-none' ?>">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h5 class="m-0">👷 Worker: <b id="workerName" class="text-primary"><?= htmlspecialchars($workerName) ?></b></h5>
      <span class="badge bg-warning text-dark fs-6">💰 Margin Work</span>
    </div>
    <div class="d-flex gap-2 flex-wrap mt-2 mt-sm-0">
      <button class="btn btn-sm btn-outline-primary" onclick="openWorkerModal()">🔁 Change Worker</button>
      <button class="btn btn-sm btn-outline-secondary" onclick="openWorkTypeModal()">⚙️ Change Category</button>
    </div>
  </div>

  <!-- 💼 Main Work Form -->
  <div id="formContent" class="<?= $workerId ? '' : 'd-none' ?>">
    <div class="row g-4">
      <!-- LEFT: Groups -->
      <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-primary text-white">Select Motor Parts</div>
          <div class="card-body">
            <div class="d-flex flex-wrap mb-3">
              <?php foreach ($groups as $g): ?>
                <div class="text-center me-2">
                  <img src="<?= htmlspecialchars($g['image_path'] ?: 'Images/placeholder.jpg') ?>"
                       class="group-img" data-group-id="<?= (int)$g['id'] ?>" 
                       alt="<?= htmlspecialchars($g['group_name']) ?>">
                  <div class="small"><?= htmlspecialchars($g['group_name']) ?></div>
                </div>
              <?php endforeach; ?>
            </div>
            <div id="parts-list" class="row g-2">
              <div class="text-muted">Select a group above to see parts.</div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT: Work Form -->
      <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-info text-white">Work Details</div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label">Work Date</label>
              <input type="date" id="workDate" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
            <div id="selected-pills" class="mb-2"></div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label">Total Cost</label>
                <input type="number" id="totalCost" class="form-control bold-input" value="0">
              </div>
              <div class="col-6">
                <label class="form-label">Final Bill</label>
                <input type="number" id="finalBill" class="form-control bold-input" placeholder="e.g. 1500">
              </div>
            </div>
            <div class="d-grid">
              <button id="saveBtn" class="btn btn-primary">💾 Save Work</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- PREVIEW TABLE -->
    <div class="card border-0 shadow-sm mt-4">
      <div class="card-header bg-light">Preview</div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead class="table-primary">
              <tr>
                <th>Date</th><th>Sr No</th><th>Motor Parts</th>
                <th>Cost</th><th>Bill</th><th>Margin</th><th>Salary</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td id="previewDate"><?= date('d-m-Y') ?></td>
                <td>1</td>
                <td id="previewParts" class="small text-wrap"></td>
                <td id="previewCost">₹0.00</td>
                <td id="previewBill">₹0.00</td>
                <td id="previewMargin">₹0.00</td>
                <td id="previewSalary" class="text-success fw-bold">₹0.00</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div id="previewStatus" class="text-muted">Status: <b>Pending</b></div>
      </div>
    </div>
  </div>
</div>
<!-- 🧍 Worker Selection Modal (Floating, Non-blocking) -->
<div id="workerModal" class="floating-modal d-none">
  <div class="modal-box">
    <div class="modal-header bg-primary text-white d-flex justify-content-between align-items-center px-3 py-2">
      <h5 class="m-0 fw-semibold">Select Worker</h5>
    </div>

    <div class="modal-body p-3" style="max-height: 400px; overflow-y: auto;">
      <ul class="list-group list-group-flush">
        <?php
        $res = $conn->query("SELECT id, name FROM workers ORDER BY name");
        while($w = $res->fetch_assoc()): ?>
          <li class="list-group-item list-group-item-action py-2"
              style="cursor:pointer;"
              onclick="chooseWorker(<?= (int)$w['id'] ?>,'<?= htmlspecialchars($w['name']) ?>')">
            👷 <?= htmlspecialchars($w['name']) ?>
          </li>
        <?php endwhile; ?>
      </ul>
    </div>

  </div>
</div>


<!-- ⚙️ Work Type Modal (Floating, Non-blocking) -->
<div id="workTypeModal" class="floating-modal d-none">
  <div class="modal-box text-center">
    <div class="modal-header bg-primary text-white d-flex justify-content-between align-items-center px-3 py-2">
      <h5 class="m-0 fw-semibold">Select Work Type</h5>
    </div>

    <div class="modal-body py-4 px-3">
      <div class="d-flex justify-content-center gap-4 flex-wrap">
        <button class="btn btn-success px-4 py-2 fw-semibold" id="btnLabouring">🧰 Labouring</button>
        <button class="btn btn-warning px-4 py-2 fw-semibold text-dark" id="btnMargin">💰 Margin</button>
      </div>
    </div>

  </div>
</div>


<!-- 🎨 Clean CSS for Floating Modals -->
<style>
.floating-modal {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 1080;
  pointer-events: none; /* Sidebar & background clickable */
}

.modal-box {
  width: 380px;
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  overflow: hidden;
  pointer-events: auto; /* Modal interactive */
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  animation: fadeIn .2s ease-in-out;
}

@keyframes fadeIn {
  from {opacity:0; transform:translate(-50%, -46%);}
  to {opacity:1; transform:translate(-50%, -50%);}
}

.list-group-item:hover {
  background-color: #f1f5ff;
}

@media (max-width: 576px) {
  .modal-box {
    width: 95%;
  }
}
</style>


<script>
const partsByGroup = <?= json_encode($partsByGroup, JSON_UNESCAPED_UNICODE) ?>;
let workerId = <?= $workerId ? $workerId : 'null' ?>;
let selectedPartIds=[], selectedPartNames=[], selectedPartCosts=[];
let manualCost=false;

// ---------- Modal Controls ----------
function openWorkerModal(){ document.getElementById('workerModal').classList.remove('d-none'); }
function closeWorkerModal(){ document.getElementById('workerModal').classList.add('d-none'); }
function openWorkTypeModal(){ document.getElementById('workTypeModal').classList.remove('d-none'); }
function closeWorkTypeModal(){ document.getElementById('workTypeModal').classList.add('d-none'); }

function chooseWorker(id, name){
  workerId=id;
  document.getElementById('workerName').innerText=name;
  closeWorkerModal();
  openWorkTypeModal();
}

function gotoMargin(id){ location.href='add_work.php?worker_id='+id; }
function gotoLabour(id){ location.href='labouring_add_work.php?worker_id='+id; }

document.getElementById('btnLabouring').onclick=()=>gotoLabour(workerId);
document.getElementById('btnMargin').onclick=()=>gotoMargin(workerId);

// ---------- Parts Logic ----------
function fmt(n){return '₹'+(Number(n||0)).toFixed(2);}
function renderParts(groupId){
  const wrap=document.getElementById('parts-list');
  wrap.innerHTML='';
  const list=partsByGroup[groupId]||[];
  if(!list.length){wrap.innerHTML='<div class="text-muted">No parts in this group.</div>';return;}
  list.forEach(p=>{
    const col=document.createElement('div');
    col.className='col-6';
    col.innerHTML=`<div class="part-card" onclick="addPart(${p.id},'${p.name}',${p.cost})">${p.name}<br><span class='text-muted'>(${fmt(p.cost)})</span></div>`;
    wrap.appendChild(col);
  });
}

function addPart(id,name,cost){
  selectedPartIds.push(id);selectedPartNames.push(name);selectedPartCosts.push(Number(cost));
  const pill=document.createElement('span');
  pill.className='pill';
  pill.innerHTML=`${name} (${fmt(cost)}) <span class='x'>&times;</span>`;
  pill.querySelector('.x').onclick=()=>{ 
    const idx=[...pill.parentNode.children].indexOf(pill);
    selectedPartIds.splice(idx,1);
    selectedPartNames.splice(idx,1);
    selectedPartCosts.splice(idx,1);
    pill.remove();recalcCost();updatePreview();
  };
  document.getElementById('selected-pills').appendChild(pill);
  recalcCost();updatePreview();
}

function recalcCost(){
  if(!manualCost){
    const costVal=selectedPartCosts.reduce((a,b)=>a+b,0);
    document.getElementById('totalCost').value=costVal.toFixed(2);
  }
}

function updatePreview(){
  let cost=Number(document.getElementById('totalCost').value)||0;
  const bill=Number(document.getElementById('finalBill').value)||0;
  const margin=bill-cost,salary=margin*0.5;
  const date=document.getElementById('workDate').value;
  const formattedDate=new Date(date).toLocaleDateString('en-GB');
  document.getElementById('previewDate').textContent=formattedDate;
  document.getElementById('previewParts').textContent=selectedPartNames.join(', ');
  document.getElementById('previewCost').textContent=fmt(cost);
  document.getElementById('previewBill').textContent=fmt(bill);
  document.getElementById('previewMargin').textContent=fmt(margin);
  document.getElementById('previewSalary').textContent=fmt(salary);
  document.getElementById('previewStatus').innerHTML='Status: <b>'+(bill>0?'Completed':'Pending')+'</b>';
}

document.querySelectorAll('.group-img').forEach(img=>img.addEventListener('click',()=>renderParts(img.dataset.groupId)));
document.getElementById('totalCost').addEventListener('input',()=>{manualCost=true;updatePreview();});
document.getElementById('finalBill').addEventListener('input',updatePreview);
document.getElementById('workDate').addEventListener('change',updatePreview);

document.getElementById('saveBtn').addEventListener('click',async()=>{
  if(!workerId){alert("Please select worker.");return;}
  if(selectedPartIds.length===0){alert("Select at least one part.");return;}
  const resp=await fetch('save_work.php',{method:'POST',headers:{'Content-Type':'application/json'},
    body:JSON.stringify({
      worker_id:workerId,
      parts:selectedPartIds,
      cost:document.getElementById('totalCost').value,
      bill:document.getElementById('finalBill').value||null,
      date:document.getElementById('workDate').value
    })});
  const data=await resp.json().catch(()=>({}));
  if(data.status==='success'){alert(data.is_pending?"Saved as Pending.":"Saved as Completed.");location.href='add_work.php?worker_id='+workerId;}
  else{alert("Save failed.");console.log(data);}
});

// ✅ Auto open worker modal (non-blocking)
window.addEventListener('load',()=>{
  if(!workerId){ setTimeout(()=>openWorkerModal(),500); }
});
</script>
</body>
</html>
