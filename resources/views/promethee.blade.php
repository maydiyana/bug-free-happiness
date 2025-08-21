@extends('layouts.base')

@section('content')
<div class="title-box d-flex gap-2 align-items-baseline">
  <i class="ri-calculator-line fs-2"></i>
  <p class="fs-3 m-0">Hasil Perhitungan PROMETHEE</p>
</div>
<div class="breadcrumbs-box rounded rounded-2 bg-white p-2 mt-2">
  <nav
    style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='currentColor'/%3E%3C/svg%3E&#34;);"
    aria-label="breadcrumb">
    <ol class="breadcrumb m-0">
      <li class="breadcrumb-item d-flex gap-2 align-items-center"><i class="ri-apps-line"></i>Metode Promethee</li>
      <li class="breadcrumb-item active" aria-current="page">Data Perhitungan</li>
    </ol>
  </nav>
</div>
<div class="content-box p-3 mt-3 rounded-2 bg-white">

  {{-- 1. Matriks Keputusan --}}
  <h5>Matriks Keputusan</h5>
  <table class="table table-bordered text-center">
    <thead>
      <tr>
        <th>Alternatif</th>
        @foreach ($criteria as $crit)
          <th>{{ $crit->name }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach ($alternatives as $alt)
      <tr>
        <td>{{ $alt->name }}</td>
        @foreach ($criteria as $crit)
          <td>{{ $decisionMatrix[$alt->id][$crit->id] }}</td>
        @endforeach
      </tr>
      @endforeach
    </tbody>
  </table>

  {{-- 2. Matriks Preferensi --}}
  <h5 class="mt-4">Matriks Preferensi</h5>
  <table class="table table-bordered text-center">
    <thead>
      <tr>
        <th>Alternatif</th>
        @foreach ($alternatives as $alt)
          <th>{{ $alt->name }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach ($alternatives as $alt_i)
      <tr>
        <th>{{ $alt_i->name }}</th>
        @foreach ($alternatives as $alt_j)
          <td>{{ number_format($prefMatrix[$alt_i->id][$alt_j->id], 4) }}</td>
        @endforeach
      </tr>
      @endforeach
    </tbody>
  </table>

  {{-- 3. Hasil Akhir --}}
  <h5 class="mt-4">Hasil Perhitungan PROMETHEE</h5>
  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th>Kode</th>
        <th>Nama Alternatif</th>
        <th>Leaving Flow</th>
        <th>Entering Flow</th>
        <th>Net Flow</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($results as $res)
      <tr>
        <td>{{ $res->code }}</td>
        <td>{{ $res->name }}</td>
        <td>{{ $res->leaving }}</td>
        <td>{{ $res->entering }}</td>
        <td>{{ $res->net }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

</div>
@endsection
