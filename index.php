<!DOCTYPE html>
<html>
    <link rel="stylesheet" href="style.css">
    <head>
        <title>DeeplTrick Editor</title>
    </head>
    <body onload="load();">
        <div class="container">
            <h3>Insert text here:</h3>
            <textarea class="text" id="input_box"></textarea>
            <form class="updateButton" action="_index.php" method="post", id="processForm">
                <input type="submit" name="Pass though Deepl Button" value="Pass though Deepl" />
            </form>
            <?php
                function reload(){
                    if ($_GET)
                    {
                        if (isset($_GET["text"]))
                        {
                            return $_GET["text"];
                        }
                    }
                    return "";
                }
            ?>
            <script>
                const textBox = document.getElementById("input_box")
                const form = document.getElementById("processForm")
                
                const setFormAction = function(txt)
                {
                    form.action = "_index.php?text=" + txt
                }
                
                const onUpdate = function(e)
                {
                    setFormAction(e.target.value);
                }
                textBox.addEventListener('input', onUpdate);
                textBox.addEventListener('propertychange', onUpdate);

                const load = function()
                {
                    document.getElementById("input_box").value = "<?php echo reload()?>";
                    setFormAction("<?php echo reload()?>")
                }
            </script>

        </div>
        <div class="container">
            <h3>Outputted Text</h3>
            <div class="text" style="border-colior: red">
            <?php
                ini_set('display_errors', 1);
                ini_set('display_startup_errors', 1);
                error_reporting(E_ALL);
                
                if ($_GET) {
                    if (isset($_GET['text']))
                    {
                        main($_GET['text']);
                    }
                }
                function split_by_sentance($txt)
                {
                    $out = array();
                    $hold = "";
                    for ($i=0; $i<strlen($txt); $i++)
                    {
                        $hold .= $txt[$i];
                        if ($txt[$i] == "." || $txt[$i]=="?" || $txt[$i]=="!")
                        {
                            $out[] = $hold;
                            $hold = "";
                        }
                    }
                    if (strlen($hold) != 0)
                    {
                        $out[] = $hold;
                    }
                    return $out;
                }

                function split_by_word($sentance)
                {
                    $words = array();
                    $wordBuffer = "";
                    for ($idx=0; $idx<strlen($sentance); $idx++)
                    {
                        $letter = $sentance[$idx];
                        if (ctype_space($letter))
                        {
                            $words[] = $wordBuffer;
                            $wordBuffer = "";
                        }
                        else
                        {
                            $wordBuffer .= $letter;
                        }
                    }
                    $words[] = $wordBuffer;
                    return array("words" => $words);
                }

                function translate_sentance($text)
                {
                    $authKey = '12d5678f-e42a-3d7c-94e6-f4978d7e1a2b:fx';
                    $translation = json_decode(shell_exec("curl https://api-free.deepl.com/v2/translate -d auth_key=".$authKey." -d 'text=".$text."' -d 'target_lang=EN' -d 'source_lang=DE'"), true);
                    $translation = json_decode(shell_exec("curl https://api-free.deepl.com/v2/translate -d auth_key=".$authKey." -d 'text=".$translation["translations"][0]["text"]."' -d 'target_lang=DE' -d 'source_lang=EN'"), true);
                    return $translation["translations"][0]["text"];
                }

                function buildSequence($orig, $corrected)
                {
                    return array($orig, $corrected);
                }

                function getHalfSectionInRange($idx, $end, $arr)
                {
                    $buffer = "";
                    for (; $idx < $end; $idx++)
                    {
                        $buffer .= " ".$arr[$idx];
                    }
                    return $buffer;
                }

                function inRange($idx1, $idx2, $arr1, $arr2)
                {
                    return ($idx1 < count($arr1) && $idx2 < count($arr2));
                }

                function getTailSequence($idx, $arr, $orig)
                {
                    $buffer = getHalfSectionInRange($idx, count($arr), $arr);

                    if ($orig)
                    {
                        return array(strlen($buffer), buildSequence($buffer, ""));
                    }
                    else
                    {
                        return array(strlen($buffer), buildSequence("[...]", $buffer));
                    }
                }
                
                function getIdentialSequence($oidx, $cidx, $orig, $corrected)
                {
                    $buffer = "";
                    while ($oidx < count($orig) && $cidx < count($corrected))
                    {
                        if ($orig[$oidx] != $corrected[$cidx])
                        {
                            return array($oidx, $cidx, buildSequence($buffer, ""));
                        }
                        $buffer .= " ".$orig[$oidx];
                        $oidx++; $cidx++;
                    }
                    return array($oidx, $cidx, buildSequence($buffer, ""));
                }

                function buildUnequalSequences($idx, $arr)
                {
                    $arr = array_slice($arr, 0, $idx);
                    return arrToStr($arr);
                }

                function buildForwardSequence($idx, $arr)
                {
                    $arr = array_slice($arr, $idx);
                    return arrToStr($arr);
                }
                
                function arrToStr($arr)
                {
                    $out = "";
                    foreach ($arr as &$word)
                    {
                        $out .= " ".$word;
                    }
                    return $out;
                }

                function getNonIdenticalSequences($oidx, $cidx, $orig, $corr)
                {
                    $originalOidx = $oidx;
                    $originalCidx = $cidx;

                    $cvisited = array();
                    $ovisited = array();
                    while (inRange($oidx, $cidx, $orig, $corr))
                    {
                        $cvisited[] = $corr[$cidx];
                        $ovisited[] = $orig[$oidx];
                        $origPos = array_search($orig[$oidx], $cvisited);
                        if (! $origPos === false)
                        {
                            array_pop($ovisited);
                            return array($oidx, $origPos+$originalCidx, array(arrToStr($ovisited), buildUnequalSequences($origPos, $cvisited)));
                        }
                        $corrPos = array_search($corr[$cidx], $ovisited);
                        if (! $corrPos === false)
                        {
                            array_pop($cvisited);
                            return array($corrPos+$originalOidx, $cidx, array(buildUnequalSequences($corrPos, $ovisited), arrToStr($cvisited)));
                        }
                        #echo $orig[$oidx].", ".$corr[$cidx].", ".json_encode($ovisited).", ".json_encode($cvisited).", ".$origPos.", ".$corrPos."<br>";
                        $oidx++; $cidx++;
                    }
                    return array(count($orig), count($corr), array(buildForwardSequence($originalOidx, $orig) ,buildForwardSequence($originalCidx, $corr)));
                }
                
                function addTailSequence($output, $idx, $arr, $orig)
                {
                    $out = getTailSequence($idx, $arr, false);
                    if ($out[0] > 0)
                    {
                        $output[] = $out[1];
                    }
                    return $output;
                }

                function getSentanceCorrectionSections($original, $corrected)
                {
                    $oidx = 0;
                    $cidx = 0;
                    $output = array();
                    while (true)
                    {
                        $out = getIdentialSequence($oidx, $cidx, $original, $corrected);
                        $oidx = $out[0]; $cidx = $out[1]; $output[] = $out[2];

                        $out = getNonIdenticalSequences($oidx, $cidx, $original, $corrected);
                        $oidx = $out[0]; $cidx = $out[1]; $output[] = $out[2];
                        if ($oidx == count($original))
                        {
                            $output = addTailSequence($output, $cidx, $corrected, false);
                            break;
                        }
                        if ($cidx == count($corrected))
                        {
                            $output = addTailSequence($output, $oidx, $original, true);
                            break;
                        }
                    }
                    return $output;
                }

                function processSentance($sentance)
                {
                    $corrected = split_by_word(translate_sentance($sentance));
                    $original = split_by_word($sentance);

                    return getSentanceCorrectionSections($original["words"], $corrected["words"]);
                }

                function renderSentance($arr)
                {
                    $idx = 0;
                    foreach ($arr as &$element)
                    {
                        if (strlen($element[1]) || strlen($element[0]))
                        {
                            if (strlen($element[1]))
                            {
                                echo <<<EOD
                                    <div class=correctedText>
                                        <div onClick="onCorrectionClick(this.id);" class="correctedText original" id=$idx>$element[0]&nbsp;</div>
                                        <div class="dropdown-content" id=$idx.dropDown>
                                            <div onClick="spawCorrection(this.id);" class="correctedText corrected" id=$idx.other>
                                                $element[1]&nbsp;
                                            </div>
                                        </div>
                                    </div>
                                EOD;
                            }
                            else
                            {
                                echo '<div class="correctedText identical" id='.$idx.'>'.$element[0].'&nbsp;</div>';
                            }
                            $idx++;
                        }
                    }
                }

                function main($txt)
                {
                    $sentances = split_by_sentance($txt);
                    $sentances = processSentance($sentances[0]);
                    renderSentance($sentances);
                }
            ?>
            <script>
                function onCorrectionClick(clicked)
                {
                    document.getElementById(clicked + ".dropDown").classList.toggle("show");
                }

                function spawCorrection(clicked)
                {
                    main = document.getElementById(clicked.replace(".other", ""));
                    other = document.getElementById(clicked);
                    
                    [main.innerHTML, other.innerHTML] = [other.innerHTML, main.innerHTML];
                    [main.className, other.className] = [other.className, main.className];

                    onCorrectionClick(main.id);
                }

                window.onclick = function(event) {
                    if (!(event.target.matches('.correctedText.original') || event.target.matches('.correctedText.corrected'))) {
                        var dropdowns = document.getElementsByClassName("dropdown-content");
                        var i;
                        for (i = 0; i < dropdowns.length; i++) {
                            var openDropdown = dropdowns[i];
                            if (openDropdown.classList.contains('show')) {
                                openDropdown.classList.remove('show');
                            }
                        }
                    }
                } 
            </script>
            </div>
        </div>
    </body>
</html>