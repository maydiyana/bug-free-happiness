@extends('layouts.base')

@section('content')
<div class="title-box d-flex gap-2 align-items-baseline">
  <i class="ri-dashboard-line fs-2"></i>
  <p class="fs-3 m-0">Dashboard</p>
</div>

<div class="breadcrumbs-box rounded rounded-2 bg-white p-2 mt-2">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb m-0">
      <li class="breadcrumb-item d-flex gap-2 align-items-center"><i class="ri-apps-line"></i>SPK RAJAWALI</li>
      <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
    </ol>
  </nav>
</div>

<!-- Menampilkan jumlah data -->
<div class="data-count-box mt-2 mb-3">
  <div class="row">
    <div class="col-md-6">
      <div class="data-count-item p-3 bg-white rounded rounded-2 border">
        <h4>Jumlah Data Kriteria :</h4>
        <p class="fs-4">{{ $criteriaCount }}</p>
      </div>
    </div>
    <div class="col-md-6">
      <div class="data-count-item p-3 bg-white rounded rounded-2 border">
        <h4>Jumlah Data Alternatif :</h4>
        <p class="fs-4">{{ $alternativesCount }}</p>
      </div>
    </div>
  </div>
</div>

<!-- Chart -->
<div class="chart-box bg-white p-3 rounded rounded-2 border mt-3">
  <h4>Diagram Jumlah Data</h4>
  <canvas id="dataChart" style="max-height:300px;"></canvas>
</div>

<!-- Load Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    let criteria = {{ $criteriaCount ?? 0 }};
    let alternatives = {{ $alternativesCount ?? 0 }};

    console.log("Criteria:", criteria, "Alternatives:", alternatives); // cek data

    const ctx = document.getElementById('dataChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Kriteria', 'Alternatif'],
                datasets: [{
                    label: 'Jumlah Data',
                    data: [criteria, alternatives],
                    backgroundColor: ['#4e73df', '#1cc88a'],
                    borderColor: ['#2e59d9', '#17a673'],
                    borderWidth: 1,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    } else {
        console.error("Canvas #dataChart tidak ditemukan!");
    }
});
</script>
@endsection
