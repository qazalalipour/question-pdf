<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        مدیریت سوالات
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            direction: rtl;
            font-family:
                Arial,
                Tahoma,
                sans-serif;

            background:
                #f5f7fa;

            margin: 0;
            padding: 40px;
        }

        .container {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
        }

        .header h1 {
            margin: 0 0 10px;
            font-size: 28px;
        }

        .header p {
            margin: 0;
            color: #666;
            font-size: 16px;
        }

        .actions {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }

        .button {
            flex: 1;
            display: block;
            text-align: center;
            padding: 15px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
        }

        .pdf-button {
            background: #dc2626;
            color: #ffffff;
        }

        .questions {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
        }

        .questions h2 {
            margin-top: 0;
            margin-bottom: 20px;
        }

        .question-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;

            padding: 15px 0;

            border-bottom:
                1px solid #eeeeee;
        }

        .question-item:last-child {
            border-bottom: none;
        }

        .question-number {
            font-weight: bold;
            font-size: 17px;
        }

        .question-actions {
            display: flex;
            gap: 10px;
        }

        .question-actions a {
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
        }

        .view-button {
            background: #2563eb;
            color: #ffffff;
        }

        .image-button {
            background: #16a34a;
            color: #ffffff;
        }

    </style>

</head>

<body>

<div class="container">

    {{-- هدر --}}

    <div class="header">

        <h1>
            مدیریت سوالات
        </h1>

        <p>
            مشاهده سوالات و تولید فایل PDF
        </p>

    </div>


    {{-- عملیات اصلی --}}

    <div class="actions">

        <a
            href="{{ route('questions.pdf') }}"
            class="button pdf-button"
        >
            📄 تولید و دانلود PDF
        </a>

    </div>


    {{-- لیست سوالات --}}

    <div class="questions">

        <h2>
            لیست سوالات
        </h2>

        @foreach($questions as $question)

            <div class="question-item">

                <div class="question-number">

                    سؤال شماره
                    {{ $question['number'] }}

                </div>


                <div class="question-actions">

                    <a
                        href="{{ route(
                            'questions.show',
                            $question['number']
                        ) }}"
                        class="view-button"
                    >
                        مشاهده
                    </a>


                    <a
                        href="{{ route(
                            'questions.image',
                            $question['number']
                        ) }}"
                        class="image-button"
                    >
                        دانلود تصویر
                    </a>

                </div>

            </div>

        @endforeach

    </div>

</div>

</body>

</html>
