<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penjumlahan Deret Fibonacci</title>
    <style>
        body {
            font-family: "Poppins", sans-serif;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            backdrop-filter: blur(8px);
            padding: 30px 40px;
            width: 400px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            animation: fadeIn 1s ease;
        }

        h1 {
            font-size: 22px;
            margin-bottom: 15px;
            letter-spacing: 1px;
            color: #ffe082;
        }

        .result {
            background: rgba(255, 255, 255, 0.15);
            padding: 15px;
            border-radius: 10px;
            margin-top: 10px;
        }

        .result span {
            font-size: 20px;
            display: block;
            margin: 5px 0;
        }

        .highlight {
            color: #ffeb3b;
            font-weight: bold;
        }

        .fib-seq {
            margin-top: 15px;
            font-size: 14px;
            color: #d1e8ff;
        }

        form {
            margin-bottom: 15px;
        }

        input[type=number] {
            width: 60px;
            padding: 8px;
            border-radius: 8px;
            border: none;
            text-align: center;
            margin: 5px;
        }

        button {
            background: #ffb300;
            color: #000;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        button:hover {
            background: #ffc947;
            transform: translateY(-2px);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🔢 Penjumlahan Deret Fibonacci</h1>

        <form method="GET" action="/">
            <label>Index 1:</label>
            <input type="number" name="n1" value="{{ $index1 }}" min="0" required>
            <label>Index 2:</label>
            <input type="number" name="n2" value="{{ $index2 }}" min="0" required>
            <button type="submit">Hitung</button>
        </form>

        <div class="result">
            <span>Fibonacci ke-{{ $index1 }} = <span class="highlight">{{ $data['bil1'] }}</span></span>
            <span>Fibonacci ke-{{ $index2 }} = <span class="highlight">{{ $data['bil2'] }}</span></span>
            <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.2); margin: 10px 0;">
            <span>Hasil Penjumlahan = <span class="highlight">{{ $data['hasil'] }}</span></span>
        </div>

        <div class="fib-seq">
            Deret Fibonacci hingga elemen ke-{{ max($index1, $index2) }}:<br>
            {{ implode(", ", $data['deret']) }}
        </div>
    </div>
</body>

</html>