<!DOCTYPE html>
<html>
    <link rel="stylesheet" href="style.css">
    <head>
        <title>DeeplTrick Editor</title>
    </head>
    <body>
        <script>
            function load()
                {
                    colorTogel(document.getElementById("colorBox"))
                }
                window.onload = load;
        </script>
        <div class="container">
            <h3>Insert text here:</h3>
            <form action="index.php" method="post" id="processForm" class=element>
                <textarea class="text" id="input_box" name="text"><?php echo reload()?></textarea>
                <div class=splitscreen>
                    <div class="element">
                        <div class="tooltip">
                            <!--<button class="copy tooltip" onClick="copy();" onmouseout="outFunc();"><img src="http://wandhoven.ddns.net/uploads/copy.png">-->
                            <input class="copy tooltip" type=button name="Copy Button" value="&#x2398" onclick="copy()" onmouseout="outFunc()"/>
                            <span class="tooltiptext" id=copyTip>
                                Coly to clipboard
                            </span>
                        </div>
                    </div>
                    <div class="element">
                        <div class="tooltip">
                            <input class=tooltip type="submit" name="Pass though Deepl Button" value="Pass though Deepl" style="display: inline-flex; display: inline-block;"/>
                            <span class="tooltiptext">
                                Apply DeepL
                            </span>
                        </div>
                    </div>
                    <div class="element">
                        <label for="hascolor">Color: </label>
                        <div class=tooltip>
                            <input onClick="colorTogel(this)" type="checkbox" id="colorBox" name="hasColor" checked>
                            <span class="tooltiptext" id=colortip>
                                remove color
                            </span>
                        </div>
                    </div>
                </div>
            </form>
            <?php
                function reload(){
                    if ($_POST)
                    {
                        if (isset($_POST["text"]))
                        {
                            return makeSafeString($_POST["text"]);
                        }
                    }
                    return "";
                }
            ?>
            <script src="node_modules/utf8/utf8.js"></script>
            <script>
                function colorTogel(box)
                {
                    affected = document.getElementsByClassName("correctedText");
                    for (i = 0; i < affected.length; i++) {
                        if (box.checked)
                        {
                            if (affected[i].classList.contains('nocolor')) {
                                affected[i].classList.remove('nocolor');
                            }
                        }
                        else
                        {
                            affected[i].classList.add("nocolor");
                        }
                    }
                    if (box.checked){document.getElementById("colortip").innerHTML="remove Color"}
                    else {document.getElementById("colortip").innerHTML="add Color"}
                }
            </script>
            <script>

                function isValid(node)
                {
                    return (node.nodeName == "DIV" && node.classList.contains("mainText") && (!node.classList.contains("hide")));
                }

                var replaceHtmlEntites = (function() {
                    var translate_re = /&(nbsp|amp|quot|lt|gt);/g;
                    var translate = {
                        "nbsp": " ",
                        "amp" : "&",
                        "quot": "\"",
                        "lt"  : "<",
                        "gt"  : ">"
                    };
                    return function(s) {
                        return ( s.replace(translate_re, function(match, entity) {
                        return translate[entity];
                        }) );
                    }
                })();

                function getText(){
                    let nodes = document.getElementById("corrctedSection").childNodes;
                    let out = "";
                    for (let i=0; i<nodes.length; i++)
                    {
                        let node = nodes[i];
                        if (isValid(node))
                        {
                            out += node.innerHTML;
                        }
                    }
                    
                    out = replaceHtmlEntites(out);
                    console.log(out);
                    return out;
                }
                
                function copy() {
                    txt = getText();
                    copyToClipboard(txt);
                    var tooltip = document.getElementById("copyTip");
                    tooltip.innerHTML = "Copied text"
                }

                function outFunc() {
                    var tooltip = document.getElementById("copyTip");
                    tooltip.innerHTML = "Copy to clipboard";
                }

                function copyToClipboard(textToCopy) {
                    // navigator clipboard api needs a secure context (https)
                    if (navigator.clipboard && window.isSecureContext) {
                        // navigator clipboard api method'
                        return navigator.clipboard.writeText(textToCopy);
                    } else {
                        // text area method
                        let textArea = document.createElement("textarea");
                        textArea.value = textToCopy;
                        // make the textarea out of viewport
                        textArea.style.position = "fixed";
                        textArea.style.left = "-999999px";
                        textArea.style.top = "-999999px";
                        document.body.appendChild(textArea);
                        textArea.focus();
                        textArea.select();
                        return new Promise((res, rej) => {
                            // here the magic happens
                            document.execCommand('copy') ? res() : rej();
                            textArea.remove();
                        });
                    }
                }

            </script>

        </div>
        <div class="container">
            <h3>Outputted Text</h3>
            <div class="text" style="border-colior: red" id="corrctedSection">
            <?php  
                ini_set('display_errors', 1);
                ini_set('display_startup_errors', 1);
                error_reporting(E_ALL);

                if ($_POST) {
                    if (isset($_POST['text']))
                    {
                        main($_POST['text']);
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
                            $out[] = ltrim($hold);
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
                
                function buildRequest($sentances, $sl, $tl, $key)
                {
                    $size = count($sentances);
                    $out = array();
                    $idx = 0;
                    while ($idx < $size)
                    {
                        $request = "curl https://api-free.deepl.com/v2/translate -d auth_key=".$key." -d 'target_lang=".$tl."' -d 'source_lang=".$sl."'";
                        $count = 0;
                        while ($idx < $size && $count < 50)
                        {
                            $request .= " -d \"text=".str_replace('"', "''", $sentances[$idx])."\"";
                            $idx++;
                            $count++;
                        }
                        $out[] = $request;
                    }
                    return $out;
                }

                function decodeTranslations($data)
                {
                    $res = array();
                    foreach($data["translations"] as &$element)
                    {
                        $res[] = str_replace("''", '"', $element["text"]);
                    }
                    return $res;
                }

                function translate($sentances, $from, $to)
                {
                    $authKey = '12d5678f-e42a-3d7c-94e6-f4978d7e1a2b:fx';
                    $requests = buildRequest($sentances, $from, $to, $authKey);
                    $out = array();

                    foreach ($requests as &$request)
                    {
                        $out = array_merge($out, decodeTranslations(json_decode(shell_exec($request), true)));
                    }
                    return $out;
                }

                function translate_sentances($text)
                {
                    $translation = translate($text, "DE", "EN");
                    $translation = translate($translation, "EN", "DE");
                    #echo json_encode($translation)."<br>";
                    return $translation;
                }

                function buildSequence($orig, $corrected)
                {
                    return array($orig, $corrected);
                }

                function getHalfSectionInRange($idx, $end, $arr)
                {
                    $buffer = array();
                    for (; $idx < $end; $idx++)
                    {
                        $buffer[] = $arr[$idx];
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
                        return array(count($buffer), buildSequence($buffer, array()));
                    }
                    else
                    {
                        return array(count($buffer), buildSequence(array("[...]"), $buffer));
                    }
                }
                
                function getIdentialSequence($oidx, $cidx, $orig, $corrected)
                {
                    $buffer = array();
                    while ($oidx < count($orig) && $cidx < count($corrected))
                    {
                        if ($orig[$oidx] != $corrected[$cidx])
                        {
                            return array($oidx, $cidx, buildSequence($buffer, array()));
                        }
                        $buffer[] = $orig[$oidx];
                        $oidx++; $cidx++;
                    }
                    return array($oidx, $cidx, buildSequence($buffer, array()));
                }

                function buildUnequalSequences($idx, $arr)
                {
                    $arr = array_slice($arr, 0, $idx);
                    return $arr;
                }

                function buildForwardSequence($idx, $arr)
                {
                    $arr = array_slice($arr, $idx);
                    return $arr;
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
                            return array($oidx, $origPos+$originalCidx, array($ovisited, buildUnequalSequences($origPos, $cvisited)));
                        }
                        $corrPos = array_search($corr[$cidx], $ovisited);
                        if (! $corrPos === false)
                        {
                            array_pop($cvisited);
                            return array($corrPos+$originalOidx, $cidx, array(buildUnequalSequences($corrPos, $ovisited), $cvisited));
                        }
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

                function processSentance($sentance, $translation)
                {
                    $corrected = split_by_word($translation);
                    $original = split_by_word($sentance);

                    return getSentanceCorrectionSections($original["words"], $corrected["words"]);
                }

                function makeSafeString($str)
                {
                    $str = str_replace("&", "&amp;", $str);
                    $str = str_replace("<", "&lt;", $str);
                    $str = str_replace(">", "&gt;", $str);
                    $str = str_replace('"', "&quot;", $str);
                    return $str;
                }

                function renderSection($section, $class, $idx)
                {
                    foreach ($section as &$word)
                    {
                        if (strlen($word)){
                            echo '<div class="correctedText '.$class.' '.$idx.' mainText">'.makeSafeString($word).'&nbsp;</div>';
                        }
                    }
                }

                function renderCorrectedSection($section, $class, $idx)
                {
                    foreach ($section as &$word)
                    {
                        if (strlen($word)){
                            echo '<div onClick="onCorrectionClick(this);" class="correctedText '.$class.' '.$idx.' mainText">'.makeSafeString($word).'&nbsp;</div>';
                        }
                    }
                }

                function renderSwapSection($orig, $corr, $idx)
                {
                    $orig_str = arrToStr($orig);
                    $corr_str = arrToStr($corr);
                    echo    '<div class="dropdown-content correctedText" id='.$idx.'.dropDown>
                                <div onClick="spawCorrection(this.id);" class="correctedText original hide '.$idx.'" id='.$idx.'.other1>'.makeSafeString($orig_str).'&nbsp;</div>
                                <div onClick="spawCorrection(this.id);" class="correctedText corrected '.$idx.'" id='.$idx.'.other2>'.makeSafeString($corr_str).'&nbsp;</div>
                            </div>';

                    renderCorrectedSection($orig, "original", $idx);
                    renderCorrectedSection($corr, "corrected hide", $idx);
                }

                function renderSentance($arr, $idx)
                {
                    foreach ($arr as &$element)
                    {
                        if (count($element[1]) || count($element[0]))
                        {
                            if (count($element[1]))
                            {
                                renderSwapSection($element[0], $element[1], $idx);
                            }
                            else
                            {
                                renderSection($element[0], "identical" ,$idx);
                            }
                            $idx++;
                        }
                    }
                    return $idx;
                }

                function main($txt)
                {
                    $sentances = split_by_sentance($txt);
                    $translations = translate_sentances($sentances);

                    $idx = 0;
                    for ($i=0; $i<count($sentances); $i++)
                    {
                        $sentance = processSentance($sentances[$i], $translations[$i]);
                        $idx = renderSentance($sentance, $idx);
                    }
                }
            ?>
            <script>
                function isInterger(value) 
                {
                    if (value.match(/^\d+$/)) {
                            return true;
                    } else {
                            return false;
                    }
                }

                function onCorrectionClick(clicked)
                {
                    clses = clicked.className.split(" ");
                    cls = "";
                    for (let i = 0; i<clses.length; i++){
                        if (isInterger(clses[i]))
                        {
                            cls = clses[i];
                            break;
                        }
                    }
                    
                    objects = document.getElementById(cls+".dropDown");
                    parent = objects.parentElement;
                    parent.insertBefore(objects, clicked);
                    if (objects.classList.contains("show"))
                    {
                        closeAllEditors();
                    }
                    else
                    {
                        closeAllEditors();
                        objects.classList.add("show");
                    }
                }

                function spawCorrection(clicked)
                {
                    id = clicked.split('.')[0];

                    clses = document.getElementsByClassName(id);
                    for (let i = 0; i<clses.length; i++)
                    {
                        if (clses[i].classList.contains("hide"))
                        {
                            clses[i].classList.remove("hide");
                        }
                        else 
                        {
                            clses[i].classList.add("hide");
                        }
                    }
                    closeAllEditors();
                }

                function closeAllEditors()
                {
                    var dropdowns = document.getElementsByClassName("dropdown-content");
                        var i;
                        for (i = 0; i < dropdowns.length; i++) {
                            var openDropdown = dropdowns[i];
                            if (openDropdown.classList.contains('show')) {
                                openDropdown.classList.remove('show');
                            }
                        }
                }

                window.onclick = function(event) {
                    if (!(event.target.matches('.correctedText.original') || event.target.matches('.correctedText.corrected'))) {
                        closeAllEditors();
                    }
                } 
            </script>
            </div>
        </div>
    </body>
</html>