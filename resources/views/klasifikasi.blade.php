<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Prediksi Kelulusan Mahasiswa</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-8">

            <div class="card shadow-lg border-0">

                <div class="card-header bg-primary text-white text-center py-4">

                     <h2 class="mb-2">
                         Sistem Prediksi Kelulusan Mahasiswa
                     </h2>

                     <p class="mb-0">
                         Implementasi Metode Klasifikasi (Naive Bayes & K-Nearest Neighbors)
                     </p>

                 </div>

                <div class="card-body">

                    <!-- Informasi Sistem -->

                    <div class="row mb-4">

                        <div class="col-md-6">

                            <div class="card border-success">
                                <div class="card-body text-center">

                                    <h6 class="text-success">
                                        Data Training
                                    </h6>

                                    <h3>
                                        {{ $totalTraining ?? 500 }}
                                    </h3>

                                    <small>
                                        Data Historis Mahasiswa
                                    </small>

                                </div>
                            </div>

                        </div>

                        <div class="col-md-6">

                             <div class="card border-info">
                                 <div class="card-body text-center">

                                     <h6 class="text-info">
                                         Algoritma
                                     </h6>

                                     <h3>
                                         {{ session('algoritma_used', 'Naive Bayes') }}
                                     </h3>

                                     <small>
                                         Classification
                                     </small>

                                 </div>
                             </div>

                        </div>

                    </div>

                    <hr>

                    <h4 class="mb-3">
                        Data Testing
                    </h4>

                    <form action="{{ url('/predict') }}" method="POST">

                         @csrf

                         <div class="row">

                             <div class="col-md-6 mb-3">

                                 <label class="form-label fw-bold">
                                     Metode Klasifikasi
                                 </label>

                                 <select
                                     class="form-select"
                                     name="algoritma"
                                     id="algoritmaSelect"
                                     required>

                                     <option value="naive_bayes" {{ old('algoritma', 'naive_bayes') == 'naive_bayes' ? 'selected' : '' }}>
                                         Naive Bayes
                                     </option>

                                     <option value="knn" {{ old('algoritma') == 'knn' ? 'selected' : '' }}>
                                         K-Nearest Neighbors (KNN)
                                     </option>

                                 </select>

                             </div>

                             <div class="col-md-6 mb-3" id="kValueContainer" style="display: none;">

                                 <label class="form-label fw-bold">
                                     Nilai K (Jumlah Tetangga)
                                 </label>

                                 <input
                                     type="number"
                                     min="1"
                                     max="500"
                                     name="k_value"
                                     id="kValueInput"
                                     class="form-control"
                                     placeholder="Contoh: 5"
                                     value="{{ old('k_value', 5) }}">

                             </div>

                         </div>

                         <div class="row">

                             <div class="col-md-6 mb-3">

                                 <label class="form-label fw-bold">
                                     IPK
                                 </label>

                                 <input
                                     type="number"
                                     step="0.01"
                                     min="0"
                                     max="4"
                                     name="ipk"
                                     class="form-control"
                                     placeholder="Contoh: 3.50"
                                     value="{{ old('ipk') }}"
                                     required>

                             </div>

                             <div class="col-md-6 mb-3">

                                 <label class="form-label fw-bold">
                                     Kehadiran (%)
                                 </label>

                                 <input
                                     type="number"
                                     min="0"
                                     max="100"
                                     name="kehadiran"
                                     class="form-control"
                                     placeholder="Contoh: 90"
                                     value="{{ old('kehadiran') }}"
                                     required>

                             </div>

                         </div>

                         <div class="row">

                             <div class="col-md-6 mb-3">

                                 <label class="form-label fw-bold">
                                     SKS Lulus
                                 </label>

                                 <input
                                     type="number"
                                     name="sks_lulus"
                                     class="form-control"
                                     placeholder="Contoh: 120"
                                     value="{{ old('sks_lulus') }}"
                                     required>

                             </div>

                             <div class="col-md-6 mb-3">

                                 <label class="form-label fw-bold">
                                     Status Kerja
                                 </label>

                                 <select
                                     class="form-select"
                                     name="status_kerja"
                                     required>

                                     <option value="">
                                         -- Pilih Status --
                                     </option>

                                     <option value="Ya" {{ old('status_kerja') == 'Ya' ? 'selected' : '' }}>
                                         Ya
                                     </option>

                                     <option value="Tidak" {{ old('status_kerja') == 'Tidak' ? 'selected' : '' }}>
                                         Tidak
                                     </option>

                                 </select>

                             </div>

                         </div>

                         <div class="d-grid mt-4">

                             <button
                                 type="submit"
                                 class="btn btn-success btn-lg">

                                 Prediksi Kelulusan

                             </button>

                         </div>

                     </form>

                </div>

                <div class="card-footer text-center text-muted">

                    Data Mining - Classification using Naive Bayes

                </div>

            </div>

        </div>

    </div>

</div>

@if(session('prediction'))

<script>

document.addEventListener('DOMContentLoaded', function() {

     Swal.fire({

         icon: '{{ session("prediction") == "Ya" ? "success" : "warning" }}',

         title: 'Hasil Prediksi',

         html: `
             <div style="text-align:left;font-size:15px;">

                 <p>
                     <strong>Status Kelulusan :</strong>
                 </p>

                 <h4 style="color:
                 {{ session('prediction') == 'Ya'
                     ? '#198754'
                     : '#dc3545' }}">
                     {{ session('prediction') == 'Ya'
                         ? '✅ Lulus Tepat Waktu'
                         : '❌ Tidak Lulus Tepat Waktu' }}
                 </h4>

                 <hr>

                 <p>
                     <strong>Algoritma :</strong>
                     <span>{{ session('algoritma_used') }}</span>
                 </p>

                 <p>
                     {{ session('algoritma_used') == 'K-Nearest Neighbors' ? 'Persentase Suara Ya :' : 'Probabilitas Ya :' }}
                     <strong>
                         {{ round(session('prob_ya',0) * 100,2) }}%
                     </strong>
                 </p>

                 <p>
                     {{ session('algoritma_used') == 'K-Nearest Neighbors' ? 'Persentase Suara Tidak :' : 'Probabilitas Tidak :' }}
                     <strong>
                         {{ round(session('prob_tidak',0) * 100,2) }}%
                     </strong>
                 </p>

                 @if(session('neighbors'))
                 <hr>
                 <p><strong>{{ session('k_value') }} Tetangga Terdekat (K-Nearest Neighbors):</strong></p>
                 <div class="table-responsive">
                     <table class="table table-sm table-striped table-bordered" style="font-size:12px;">
                         <thead>
                             <tr class="table-dark">
                                 <th>ID</th>
                                 <th>IPK</th>
                                 <th>Kehadiran</th>
                                 <th>SKS</th>
                                 <th>Kerja</th>
                                 <th>Tepat Waktu</th>
                                 <th>Jarak</th>
                             </tr>
                         </thead>
                         <tbody>
                             @foreach(session('neighbors') as $neighbor)
                             <tr>
                                 <td>#{{ $neighbor['id'] }}</td>
                                 <td>{{ number_format($neighbor['ipk'], 2) }}</td>
                                 <td>{{ $neighbor['kehadiran'] }}%</td>
                                 <td>{{ $neighbor['sks_lulus'] }}</td>
                                 <td>{{ $neighbor['status_kerja'] }}</td>
                                 <td>
                                     <span class="badge {{ $neighbor['tepat_waktu'] == 'Ya' ? 'bg-success' : 'bg-warning text-dark' }}">
                                         {{ $neighbor['tepat_waktu'] }}
                                     </span>
                                 </td>
                                 <td>{{ $neighbor['distance'] }}</td>
                             </tr>
                             @endforeach
                         </tbody>
                     </table>
                 </div>
                 @endif

             </div>
         `,

         width: 700,
         confirmButtonText: 'Tutup'

     });

});

</script>

@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    const algoritmaSelect = document.getElementById('algoritmaSelect');
    const kValueContainer = document.getElementById('kValueContainer');
    const kValueInput = document.getElementById('kValueInput');

    function toggleKValue() {
        if (algoritmaSelect.value === 'knn') {
            kValueContainer.style.display = 'block';
            kValueInput.setAttribute('required', 'required');
        } else {
            kValueContainer.style.display = 'none';
            kValueInput.removeAttribute('required');
        }
    }

    if (algoritmaSelect) {
        algoritmaSelect.addEventListener('change', toggleKValue);
        toggleKValue();
    }
});
</script>

</body>

</html>