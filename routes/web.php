<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    function fibonacci($n)
    {
        $fib = [0, 1];
        for ($i = 2; $i < $n; $i++) {
            $fib[$i] = $fib[$i - 1] + $fib[$i - 2];
        }
        return $fib;
    }

    function jumlahDuaFibonacci($index1, $index2)
    {
        $maxIndex = max($index1, $index2) + 1;
        $deret = fibonacci($maxIndex);

        $bil1 = $deret[$index1];
        $bil2 = $deret[$index2];
        $hasil = $bil1 + $bil2;

        return [
            'bil1' => $bil1,
            'bil2' => $bil2,
            'hasil' => $hasil,
            'deret' => $deret
        ];
    }

    $index1 = request()->query('n1', 1);
    $index2 = request()->query('n2', 4);
    $data = jumlahDuaFibonacci($index1, $index2);

    return view('welcome', compact('data', 'index1', 'index2'));
});
