# DeepL-Trick-Editor
The deepL trick Editor is a web based application for marking and editing corrections made by deepL on texts. The Editor will automatically call deepl in such a way that it corrects mistakes in German texts. 
## DeepL Trick
The deepL trick is a trick that uses deepL to correct German texts. The trick works by translating it to English and then back to German.
## Instalation
To use the deepL trick editor you will need a deepL API authentication key. You can get one by going to https://www.deepl.com/pro#developer, selecting a plan and than following the account creation steps. Than download the repository, run the `setup.sh` file and follow the instructions.
```
$ git clone https://github.com/JulianWww/DeepL-Trick-Editor
$ cd DeepL-Trick-Editor && ./setup.sh
```

 ## Usage 
 To use the deepL trick editor just insert you test in the section labeled "Insert text here" than click the "pass though deepL" button. The editor will mark every text section that was changed by deepL in read. When you click it, a selection menu will appear showing the corrected sequence. If the corrected sequence is clicked it will be swapped with the original one. The copy button (left of the "pass though deepL") will copy the corrected text to the clipboard.
## Known bugs
-	If you mark the text and than copy it a "\n" will be added at the end of every word.
