<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        body {
            direction: rtl;
            font-family: Arial, Tahoma, sans-serif;
        }

        .question {
            width: 1000px;
            padding: 40px;
            margin: 0 auto;
            background: #ffffff;
            font-family: Arial, Tahoma, sans-serif;
        }

        .question-content {
            font-family: Arial, Tahoma, sans-serif;
            font-size: 20px;
            line-height: 2.5;
            margin-bottom: 30px;
        }

        .options {
            display: flex;
            flex-direction: column;
            gap: 15px;
            font-family: Arial, Tahoma, sans-serif;
        }

        .option {
            font-family: Arial, Tahoma, sans-serif;
            font-size: 18px;
            line-height: 2.5;
        }

        .correct-answer {
            margin-top: 30px;
            font-family: Arial, Tahoma, sans-serif;
            font-size: 18px;
            font-weight: bold;
        }

        .explanation {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-family: Arial, Tahoma, sans-serif;
        }

        .explanation h3 {
            margin-bottom: 20px;
            font-family: Arial, Tahoma, sans-serif;
            font-size: 20px;
        }

        .explanation-item {
            margin-bottom: 20px;
            font-family: Arial, Tahoma, sans-serif;
            font-size: 18px;
            line-height: 2.5;
        }

        /*
        |--------------------------------------------------------------------------
        | MathML
        |--------------------------------------------------------------------------
        */

        math {
            font-family: "Cambria Math", "STIX Two Math", serif;
            font-size: 1.5rem;
            direction: ltr;
            unicode-bidi: isolate;
        }

        .math-inline {
            display: inline-block;
            vertical-align: middle;
            direction: ltr;
            font-family: "Cambria Math", "STIX Two Math", serif;
        }

        .math-block {
            display: block;
            text-align: center;
            margin: 15px 0;
            direction: ltr;
            font-family: "Cambria Math", "STIX Two Math", serif;
        }
    </style>
</head>

<body>

    <div class="question">

        <div class="question-content">
            {{ $question['number'] }} -
            @foreach($question['content'] as $item)
            @if($item['type'] === 'text')
            {!! nl2br(e($item['value'])) !!}
            @elseif($item['type'] === 'math')
            <span class="math-inline">
                {!! $item['value'] !!}
            </span>
            @endif
            @endforeach
        </div>

        <div class="options">
            @foreach($question['options'] as $number => $option)
            <div class="option">
                <strong>
                    {{ $number }})
                </strong>
                @foreach($option['content'] as $item)
                @if($item['type'] === 'text')
                {!! nl2br(e($item['value'])) !!}
                @elseif($item['type'] === 'math')
                <span class="math-inline">
                    {!! $item['value'] !!}
                </span>
                @endif
                @endforeach
            </div>
            @endforeach

        </div>

        @if($question['correct_answer'] !== null)
        <div class="correct-answer">
            گزینه «{{ $question['correct_answer'] }}» صحیح است.
        </div>
        @endif

        @if(!empty($question['explanation']))
        <div class="explanation">
            <h3>
                توضیحات
            </h3>
            @foreach($question['explanation'] as $explanation)
            <div class="explanation-item">
                @foreach($explanation['content'] as $item)
                @if($item['type'] === 'text')
                {!! nl2br(e($item['value'])) !!}
                @elseif($item['type'] === 'math')
                <span class="math-inline">
                    {!! $item['value'] !!}
                </span>
                @endif
                @endforeach
            </div>
            @endforeach
        </div>
        @endif
    </div>
</body>

</html>
