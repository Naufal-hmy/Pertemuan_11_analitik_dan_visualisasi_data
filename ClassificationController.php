<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mahasiswa;

class ClassificationController extends Controller
{
    public function index()
    {
        $totalTraining = Mahasiswa::count();

        return view('klasifikasi', compact('totalTraining'));
    }

    public function predict(Request $request)
    {
        $request->validate([
            'ipk' => 'required|numeric',
            'kehadiran' => 'required|numeric',
            'sks_lulus' => 'required|numeric',
            'status_kerja' => 'required',
            'algoritma' => 'required|in:naive_bayes,knn',
            'k_value' => 'required_if:algoritma,knn|nullable|integer|min:1'
        ]);

        $total = Mahasiswa::count();

        if ($total == 0) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Data training tidak ditemukan.');
        }

        $algoritma = $request->algoritma;

        if ($algoritma === 'naive_bayes') {
            $totalYa = Mahasiswa::where('tepat_waktu', 'Ya')->count();
            $totalTidak = Mahasiswa::where('tepat_waktu', 'Tidak')->count();

            $pYa = $totalYa / $total;
            $pTidak = $totalTidak / $total;

            /*
            |--------------------------------------------------------------------------
            | KATEGORISASI DATA TESTING
            |--------------------------------------------------------------------------
            */

            $ipkTinggi = $request->ipk >= 3;
            $hadirTinggi = $request->kehadiran >= 80;
            $sksTinggi = $request->sks_lulus >= 110;

            /*
            |--------------------------------------------------------------------------
            | PROBABILITAS IPK
            |--------------------------------------------------------------------------
            */

            $ipkYa = Mahasiswa::where('tepat_waktu', 'Ya')
                ->where('ipk', $ipkTinggi ? '>=' : '<', 3)
                ->count();

            $ipkTidak = Mahasiswa::where('tepat_waktu', 'Tidak')
                ->where('ipk', $ipkTinggi ? '>=' : '<', 3)
                ->count();

            /*
            |--------------------------------------------------------------------------
            | PROBABILITAS KEHADIRAN
            |--------------------------------------------------------------------------
            */

            $hadirYa = Mahasiswa::where('tepat_waktu', 'Ya')
                ->where('kehadiran', $hadirTinggi ? '>=' : '<', 80)
                ->count();

            $hadirTidak = Mahasiswa::where('tepat_waktu', 'Tidak')
                ->where('kehadiran', $hadirTinggi ? '>=' : '<', 80)
                ->count();

            /*
            |--------------------------------------------------------------------------
            | PROBABILITAS SKS
            |--------------------------------------------------------------------------
            */

            $sksYa = Mahasiswa::where('tepat_waktu', 'Ya')
                ->where('sks_lulus', $sksTinggi ? '>=' : '<', 110)
                ->count();

            $sksTidak = Mahasiswa::where('tepat_waktu', 'Tidak')
                ->where('sks_lulus', $sksTinggi ? '>=' : '<', 110)
                ->count();

            /*
            |--------------------------------------------------------------------------
            | STATUS KERJA
            |--------------------------------------------------------------------------
            */

            $kerjaYa = Mahasiswa::where('tepat_waktu', 'Ya')
                ->where('status_kerja', $request->status_kerja)
                ->count();

            $kerjaTidak = Mahasiswa::where('tepat_waktu', 'Tidak')
                ->where('status_kerja', $request->status_kerja)
                ->count();

            /*
            |--------------------------------------------------------------------------
            | LAPLACE SMOOTHING
            |--------------------------------------------------------------------------
            */

            $pIpkYa = ($ipkYa + 1) / ($totalYa + 2);
            $pIpkTidak = ($ipkTidak + 1) / ($totalTidak + 2);

            $pHadirYa = ($hadirYa + 1) / ($totalYa + 2);
            $pHadirTidak = ($hadirTidak + 1) / ($totalTidak + 2);

            $pSksYa = ($sksYa + 1) / ($totalYa + 2);
            $pSksTidak = ($sksTidak + 1) / ($totalTidak + 2);

            $pKerjaYa = ($kerjaYa + 1) / ($totalYa + 2);
            $pKerjaTidak = ($kerjaTidak + 1) / ($totalTidak + 2);

            /*
            |--------------------------------------------------------------------------
            | NAIVE BAYES
            |--------------------------------------------------------------------------
            */

            $probYa =
                $pYa *
                $pIpkYa *
                $pHadirYa *
                $pSksYa *
                $pKerjaYa;

            $probTidak =
                $pTidak *
                $pIpkTidak *
                $pHadirTidak *
                $pSksTidak *
                $pKerjaTidak;

            $hasil = $probYa > $probTidak
                ? 'Ya'
                : 'Tidak';

            return redirect('/')
                ->withInput()
                ->with('prediction', $hasil)
                ->with('algoritma_used', 'Naive Bayes')
                ->with('prob_ya', $probYa)
                ->with('prob_tidak', $probTidak);
        } else {
            // K-Nearest Neighbors (KNN)
            $k = intval($request->k_value ?? 5);

            // Fetch training data
            $training = Mahasiswa::all();

            // Calculate min/max for normalization
            $minIpk = Mahasiswa::min('ipk') ?? 0.0;
            $maxIpk = Mahasiswa::max('ipk') ?? 4.0;
            $minHadir = Mahasiswa::min('kehadiran') ?? 0;
            $maxHadir = Mahasiswa::max('kehadiran') ?? 100;
            $minSks = Mahasiswa::min('sks_lulus') ?? 0;
            $maxSks = Mahasiswa::max('sks_lulus') ?? 144;

            $rangeIpk = ($maxIpk - $minIpk > 0) ? ($maxIpk - $minIpk) : 1;
            $rangeHadir = ($maxHadir - $minHadir > 0) ? ($maxHadir - $minHadir) : 1;
            $rangeSks = ($maxSks - $minSks > 0) ? ($maxSks - $minSks) : 1;

            $distances = [];
            foreach ($training as $row) {
                // Min-Max Normalize differences
                $normDiffIpk = ($request->ipk - $row->ipk) / $rangeIpk;
                $normDiffHadir = ($request->kehadiran - $row->kehadiran) / $rangeHadir;
                $normDiffSks = ($request->sks_lulus - $row->sks_lulus) / $rangeSks;
                
                // Categorical feature status_kerja ('Ya' / 'Tidak')
                $diffKerja = ($request->status_kerja === $row->status_kerja) ? 0 : 1;

                // Euclidean Distance
                $distVal = sqrt(
                    pow($normDiffIpk, 2) +
                    pow($normDiffHadir, 2) +
                    pow($normDiffSks, 2) +
                    pow($diffKerja, 2)
                );

                $distances[] = [
                    'row' => $row,
                    'distance' => $distVal
                ];
            }

            // Sort by distance ASC
            usort($distances, function ($a, $b) {
                return $a['distance'] <=> $b['distance'];
            });

            // Get top K neighbors
            $neighbors = array_slice($distances, 0, $k);

            // Count votes
            $countYa = 0;
            $countTidak = 0;
            $neighborDetails = [];

            foreach ($neighbors as $n) {
                $row = $n['row'];
                if ($row->tepat_waktu === 'Ya') {
                    $countYa++;
                } else {
                    $countTidak++;
                }

                $neighborDetails[] = [
                    'id' => $row->id,
                    'ipk' => floatval($row->ipk),
                    'kehadiran' => intval($row->kehadiran),
                    'sks_lulus' => intval($row->sks_lulus),
                    'status_kerja' => $row->status_kerja,
                    'tepat_waktu' => $row->tepat_waktu,
                    'distance' => round($n['distance'], 4)
                ];
            }

            $hasil = $countYa >= $countTidak ? 'Ya' : 'Tidak';

            $probYa = $countYa / $k;
            $probTidak = $countTidak / $k;

            return redirect('/')
                ->withInput()
                ->with('prediction', $hasil)
                ->with('algoritma_used', 'K-Nearest Neighbors')
                ->with('k_value', $k)
                ->with('prob_ya', $probYa)
                ->with('prob_tidak', $probTidak)
                ->with('neighbors', $neighborDetails);
        }
    }
}