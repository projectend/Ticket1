# Ticket1

```sh
การทำงานเริ่มจากไฟล์
Config/Bootstrap
->Controller/ReportPoDaysController
->Controller/Component/MyCurlComponent
->http://10.135.70.60:3067/apiCallStoredAm
```
```sh
การทำงานด้าน Server
Dockerfile
->pm2
->src/app.js
->app.post("/apiCallStoredAm", async function (req, res, next) {....} )
```
