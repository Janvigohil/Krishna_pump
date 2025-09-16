<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit(); }
require 'db_config.php';

// preload workers
$workers = $conn->query("SELECT id, name FROM workers ORDER BY name");

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
<html>
<head>
  <meta charset="utf-8">
  <title>Add Work - Krishna Pump</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{background:#f4f5fa}
    .group-img{cursor:pointer;width:70px;height:70px;margin:4px;object-fit:contain;
               border-radius:8px;border:2px solid transparent;transition:.2s}
    .group-img:hover{border-color:#0d6efd;transform:scale(1.03)}
    .part-card{border:1px solid #dee2e6;border-radius:6px;padding:6px;text-align:center;
               font-weight:bold;font-size:0.9rem;cursor:pointer}
    .pill{border:1px solid #e5e7eb;border-radius:999px;padding:.25rem .6rem;
          font-size:.85rem;margin:.15rem;display:inline-flex;gap:.4rem;align-items:center}
    .pill .x{cursor:pointer;font-weight:bold}
    .worker-bar{background:#f8f9fa;padding:6px 12px;margin-bottom:10px;
                border:1px solid #dee2e6;border-radius:6px;display:flex;justify-content:space-between;align-items:center}
    .bold-input{font-weight:bold;}
  </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main" style="margin-left:250px;padding:20px;max-width:1200px;">
  <!-- worker info bar -->
  <div id="workerBar" class="worker-bar d-none">
    <span>👷 <b id="workerName"></b></span>
    <button class="btn btn-sm btn-outline-primary" onclick="openWorkerModal()">Change Worker</button>
  </div>

  <div id="formContent" class="d-none">
    <div class="row g-4">
      <!-- LEFT: Groups + Parts -->
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

      <!-- RIGHT: Selected parts + cost/bill + date -->
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

    <!-- Preview Table -->
    <div class="card border-0 shadow-sm mt-4">
      <div class="card-header bg-light">Preview</div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead class="table-primary">
              <tr>
                <th>Date</th><th>Sr No</th><th>Motor Parts List</th>
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

<!-- Worker Selection Modal -->
<div class="modal fade" id="workerModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select Worker</h5>
      </div>
      <div class="modal-body">
        <ul class="list-group">
          <?php
          $res = $conn->query("SELECT id, name FROM workers ORDER BY name");
          while($w = $res->fetch_assoc()): ?>
            <li class="list-group-item list-group-item-action"
                onclick="chooseWorker(<?= (int)$w['id'] ?>,'<?= htmlspecialchars($w['name']) ?>')">
              👷 <?= htmlspecialchars($w['name']) ?>
            </li>
          <?php endwhile; ?>
        </ul>
      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const partsByGroup = <?php echo json_encode($partsByGroup, JSON_UNESCAPED_UNICODE); ?>;
let workerId=null, selectedPartIds=[], selectedPartNames=[], selectedPartCosts=[];
let manualCost=false;

function openWorkerModal(){ new bootstrap.Modal(document.getElementById('workerModal')).show(); }

function chooseWorker(id,name){
  workerId=id;
  document.getElementById('workerName').innerText=name;
  document.getElementById('workerBar').classList.remove('d-none');
  document.getElementById('formContent').classList.remove('d-none');
  bootstrap.Modal.getInstance(document.getElementById('workerModal')).hide();
}

function fmt(n){ return '₹' + (Number(n||0)).toFixed(2); }

function renderParts(groupId){
  const wrap=document.getElementById('parts-list');
  wrap.innerHTML='';
  const list=partsByGroup[groupId]||[];
  if(!list.length){ wrap.innerHTML='<div class="text-muted">No parts in this group.</div>'; return; }
  list.forEach(p=>{
    const col=document.createElement('div');
    col.className='col-6';
    col.innerHTML=`<div class="part-card" onclick="addPart(${p.id},'${p.name}',${p.cost})">${p.name}<br><span class="text-muted">(${fmt(p.cost)})</span></div>`;
    wrap.appendChild(col);
  });
}

function addPart(id,name,cost){
  selectedPartIds.push(id); selectedPartNames.push(name); selectedPartCosts.push(Number(cost));
  const pill=document.createElement('span');
  pill.className='pill';
  pill.innerHTML=`${name} (${fmt(cost)}) <span class="x">&times;</span>`;
  pill.querySelector('.x').onclick=()=>{ 
    const idx=[...pill.parentNode.children].indexOf(pill);
    selectedPartIds.splice(idx,1);
    selectedPartNames.splice(idx,1);
    selectedPartCosts.splice(idx,1);
    pill.remove();
    recalcCost();
    updatePreview();
  };
  document.getElementById('selected-pills').appendChild(pill);
  recalcCost();
  updatePreview();
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
  const margin=bill-cost, salary=margin*0.5;

  const date=document.getElementById('workDate').value;
  const dObj=new Date(date);
  const formattedDate=dObj.toLocaleDateString('en-GB'); // dd-mm-yyyy

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
  if(data.status==='success'){alert(data.is_pending?"Saved as Pending.":"Saved as Completed.");location.href='add_work.php?id='+workerId;}
  else{alert("Save failed.");console.log(data);}
});

// show modal on load
window.addEventListener('load',()=>openWorkerModal());
</script>
</body>
</html>
