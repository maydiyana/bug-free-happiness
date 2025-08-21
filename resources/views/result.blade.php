@extends('layouts.base')

@section('content')
<div class="title-box  d-flex gap-2 align-items-baseline"><i class="ri-calculator-line fs-2"></i>
  <p class="fs-3 m-0">Data Hasil Akhir</p>
</div>
<div class="breadcrumbs-box mt-2 rounded rounded-2 bg-white p-2">
  <nav
    style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='currentColor'/%3E%3C/svg%3E&#34;);"
    aria-label="breadcrumb">
    <ol class="breadcrumb m-0">
      <li class="breadcrumb-item d-flex gap-2 align-items-center"><i class="ri-apps-line"></i>SPK RAJAWALI</li>
      <li class="breadcrumb-item active" aria-current="page">Data Hasil Akhir</li>
    </ol>
  </nav>
</div>
<div class="content-box p-3 mt-3 rounded rounded-2 bg-white d-flex flex-column gap-4">
    <div class="card-header d-flex gap-1">
      <i class="ri-table-2"></i>Metode TOPSIS
    </div>
    <div class="card-body content p-1">
      <table id="rankTable" class="table table-striped table-hover " style="width: 100%">
        <thead>
          <tr>
            <th>Kode Alternatif</th>
            <th>Nama Alternatif</th>
            <th>Nilai</th>
            <th>Ranking</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($sortedResults as $sortedResult)
          <tr>
            <td>{{$sortedResult->code}}</td>
            <td>{{$sortedResult->name}}</td>
            <td>{{$sortedResult->grade}}</td>
            <td>{{$sortedResult->rank}}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
      
      <div class="card-header d-flex gap-1">
      <i class="ri-table-2"></i>Metode PROMETHEE
    </div>
    <div>
    <table class="table table-striped table-hover">
      <thead>
        <tr>
          <th>Kode Alternatif</th>
          <th>Nama Alternatif</th>
          <th>Nilai</th>
          <th>Ranking</th>
        </tr>
      </thead>
      <tbody>
           @foreach($prometheeRanking as $alt)
            <tr>
              <td>{{ $alt->code }}</td>
              <td>{{ $alt->name }}</td>
              <td>{{ number_format($alt->netflow, 4) }}</td>
              <td>{{ $alt->rank }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
  </div>
<!-- Diagram Perbandingan Peringkat (1 = terbaik) -->
<div class="mt-4">
  <h5 class="fw-bold">Diagram Perbandingan Peringkat</h5>
  <canvas id="compareRankChart"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
  const labels = [
    @foreach ($sortedResults as $topsisAlt)
      "{{ $topsisAlt->name }}",
    @endforeach
  ];

  // Ambil ranking asli
  const ranksTopsis = [
    @foreach ($sortedResults as $topsisAlt)
      {{ $topsisAlt->rank }},
    @endforeach
  ];

  const ranksProm = [
    @foreach ($sortedResults as $topsisAlt)
      @php $promAlt = $prometheeRanking->firstWhere('code', $topsisAlt->code); @endphp
      {{ $promAlt ? $promAlt->rank : 'null' }},
    @endforeach
  ];

  // Hitung max rank dari data yang ada
  const allRanks = [...ranksTopsis, ...ranksProm.filter(v => v !== null)];
  const maxRank = Math.max.apply(null, allRanks);

  // Konversi ranking -> skor (lebih besar = lebih baik)
  const toScore = (rank) => (rank === null ? null : (maxRank - rank + 1));
  const scoresTopsis = ranksTopsis.map(toScore);
  const scoresProm   = ranksProm.map(toScore);

  new Chart(document.getElementById('compareRankChart'), {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [
        { label: 'TOPSIS (skor peringkat)',    data: scoresTopsis, backgroundColor: 'rgba(54,162,235,0.8)' },
        { label: 'PROMETHEE (skor peringkat)', data: scoresProm,   backgroundColor: 'rgba(255,99,132,0.8)' }
      ]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'top' },
        tooltip: {
          callbacks: {
            // Tampilkan info rank asli di tooltip
            label: function(ctx) {
              const i = ctx.dataIndex;
              const method = ctx.dataset.label.includes('TOPSIS') ? 'TOPSIS' : 'PROMETHEE';
              const rank = method === 'TOPSIS' ? ranksTopsis[i] : ranksProm[i];
              return `${method}: peringkat ${rank}`;
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          title: { display: true, text: 'Skor (peringkat 1 = skor tertinggi)' },
          ticks: { precision: 0, stepSize: 1 }
        }
      }
    }
  });
});

</script>
<!-- Diagram Perbandingan Peringkat (1 = terbaik) -->
  <canvas id="compareRankChart"></canvas>

  <!-- Keterangan hasil perbandingan -->
  <div class="mt-3 p-3 bg-light border rounded">
    <h6 class="fw-bold">Keterangan Hasil:</h6>
    <p>
      Diagram di atas menampilkan perbandingan peringkat alternatif antara metode 
      <span class="fw-bold text-primary">TOPSIS</span> dan 
      <span class="fw-bold text-danger">PROMETHEE</span>.
    </p>
    <ul class="mb-0">
      @foreach ($sortedResults as $topsisAlt)
        @php 
          $promAlt = $prometheeRanking->firstWhere('code', $topsisAlt->code); 
        @endphp
        <li>
          <strong>{{ $topsisAlt->name }}</strong>: 
          TOPSIS = peringkat {{ $topsisAlt->rank }}, 
          PROMETHEE = peringkat {{ $promAlt->rank ?? '-' }}
        </li>
      @endforeach
    </ul>
    <p class="mt-2">
      Dari keterangan di atas dapat dilihat apakah terdapat perbedaan hasil peringkat
      antara kedua metode. Perbedaan ini bisa menjadi pertimbangan dalam memilih metode 
      yang paling sesuai dengan kebutuhan pengambilan keputusan.
    </p>
  </div>
</div>

    </div>
  </div>
</div>

@endsection