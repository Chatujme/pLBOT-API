# pLBOT-API
Api pro získávání dat pro IRC bota pLBOT

pLBOT API 1.00

Metody:

* Svátky
    - <apiurl>/svatky    #Výpis svátku predevčírem / včera / dnes / zítra
    - <apiurl>/svatky/dnes    #Výpis svátku dnes
    - <apiurl>/svatky/zitra    #Výpis svátku zitra


* Počasí
    ** Defaultne pro Prahu **

    - <apiurl>/pocasi    #Výpis počasí dnes / zítra / pozítří
    - <apiurl>/pocasi/dnes    #Výpis počasí dnes
    - <apiurl>/pocasi/zitra    #Výpis počasí zítra
    - <apiurl>/pocasi/pozitri    #Výpis počasí pozítří
    
    ** Pro město Brno **
    
    - <apiurl>/pocasi?mesto=brno    #Výpis počasí dnes / zítra / pozítří
    - <apiurl>/pocasi/dnes?mesto=brno    #Výpis počasí dnes pro Brno
    - <apiurl>/pocasi/zitra?mesto=brno    #Výpis počasí zítra pro Brno
    - <apiurl>/pocasi/pozitri?mesto=brno    #Výpis počasí pozítří pro Brno

    ** Pro město Plzeň **
    
    - <apiurl>/pocasi?mesto=plzen    #Výpis počasí dnes / zítra / pozítří (parametr bez diakritiky)
    - <apiurl>/pocasi?mesto=Plze%C5%88    #Výpis počasí dnes / zítra / pozítří (urlencode parametru s diakritikou)
    - <apiurl>/pocasi/dnes?mesto=Plzen    #Výpis počasí dnes pro Brno
    - <apiurl>/pocasi/zitra?mesto=Plzen    #Výpis počasí zítra pro Brno
    - <apiurl>/pocasi/pozitri?mesto=Plzen    #Výpis počasí pozítří pro Brno
    
    ...

* Horoskop
    - <apiurl>/horoskop/lev    #Výpis horoskopu pro znamení lev
    - <apiurl>/horoskop/%C5%A1t%C3%ADr    #Výpis horoskopu pro znamení šťír (urlencode parametru s diakritikou)
    - <apiurl>/horoskop/stir    #Výpis horoskopu pro znamení šťír (parametr bez diakritiky)
    ...

    
* TV stanice
    - <apiurl>/tv    #Výpis stanic
    - <apiurl>/tv/vse    #Výpis vsech dostupných programů a jejich aktuálního programu
    - <apiurl>/tv/nova   #Výpis aktuálního programu na TV NOVA
    ...
