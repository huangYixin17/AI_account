<h1>專題：自動辨識記帳機器人</h1>
<h2>專題說明：</h2>
使用LINE APP去紀錄每次輸入的記帳金額，以月份為單位紀錄
<h2>系統說明：</h2>
<p>取得使用者在LINE輸入的文字，經過dialogflow辨識後，取得使用者輸入的金額，接著讀取資料表(account_user.account)，取得上一次累加的金額，將上一次的金額加上這次的金額，回傳給使用者。</p>

<b>使用MySQL操作資料庫；使用php接收LINE回傳的資料，以及程式處理</b>
<h2>專案優點：</h2>
記帳不需要另外下載其他軟體，可直接在LINE上面記錄，並直接知道該月的累積金額
<h2>資料表：</h2>

![tables](https://user-images.githubusercontent.com/32304213/152724067-c21f5657-c718-4ab7-b0c2-7ea119dcc0a3.png)

<h2>實際操作：</h2>

![image](https://user-images.githubusercontent.com/32304213/152734815-b732b120-cb53-448c-948b-054b20b0965a.png)
