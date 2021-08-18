require("dotenv").config({ path: "../.env" });
const express = require("express");
const cors = require("cors")
const client = require("../database/dbamin");
const Logger = require("../utils/logger");
const app = express();
const port = process.env.LISTENING_PORT;

// Parse JSON bodies (as sent by API clients)
app.use(express.json());
app.use(cors());
// app.use((req, res, next) => {
//     res.header("Access-Control-Allow-Origin", "*");
//     res.header("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept");
//     next()
// })

let typeError = "AE01";
let typeErrorMess = "TYPE ERROR!";
let formatError = "AE02";
let formatErrorMess = "FORMAT ERROR!";
let stroedError = "AE03";
let apiKeyError = "AE04";
let formatApiKeyErrorMess = "API_KEY INCORRECT!";
app.post("/apiCallStoredAm", async function (req, res, next) {
    // console.log(req);
    console.log(req.headers);
    console.log(req.headers.api_key, process.env.API_KEY);
    if (req.headers.api_key == process.env.API_KEY || req.headers.apikey == process.env.API_KEY || req.headers['x-api-key'] == process.env.API_KEY) {
        new Logger().info("request apiCallStoredAm : " + JSON.stringify(req.body.data));
        client.query("SELECT * FROM parameter_check2('" + req.body.data[0] + "')", (err, result) => {
            if (result) {
                new Logger().info("response parameter_check2 : " + JSON.stringify(result.rows));
                let checkBeforeReq = true;
                for (let i = 0; i < result.rows.length; i++) {
                    //check number of params
                    if (result.rows[i].arr_type[0] === "No input parameter" && req.body.data.length === 1) {
                        new Logger().info("request " + req.body.data[0]);
                        client.query("SELECT * FROM " + req.body.data[0] + "()", (err, result) => {
                            if (result) {
                                new Logger().info("response " + req.body.data[0] + " : " + JSON.stringify(result.rows));
                                res.status(200).send(result.rows);
                            } else {
                                console.log("err store", err);
                                let josnError = { code: stroedError, message: req.body.data[0] + " " + err.message };
                                new Logger().error(req.body.data[0] + " : " + JSON.stringify(josnError) + " Query : " + req.body.data[0]);
                                res.status(200).send(josnError);
                            }
                        });
                        return;
                    } else {
                        if (req.body.data.length === 3) {
                            if (result.rows[i].arr_type.length === req.body.data[2].length && req.body.data[2].length === req.body.data[1].length) {
                                for (let j = 0; j < result.rows[i].arr_type.length; j++) {
                                    //check type params
                                    if (JSON.stringify(result.rows[i].arr_type) == JSON.stringify(req.body.data[2])) {
                                        //check value and type, both must be match
                                        let params = "";
                                        let stringType = "";
                                        for (let k = 0; k < req.body.data[2].length; k++) {
                                            if (
                                                req.body.data[2][k] === "text" ||
                                                req.body.data[2][k] === "character" ||
                                                req.body.data[2][k] === "character varying" ||
                                                req.body.data[2][k] === "date" ||
                                                req.body.data[2][k] === "timestamp without time zone" ||
                                                req.body.data[2][k] === "timestamp with time zone"
                                            ) {
                                                stringType = "string";
                                            } else if (
                                                req.body.data[2][k] === "text[]" ||
                                                req.body.data[2][k] === "character varying[]" ||
                                                req.body.data[2][k] === "date[]" ||
                                                req.body.data[2][k] === "timestamp without time zone[]" ||
                                                req.body.data[2][k] === "timestamp with time zone[]"
                                            ) {
                                                stringType = "string[]";
                                            } else if (req.body.data[2][k] === "integer" || req.body.data[2][k] === "numeric") {
                                                stringType = "number";
                                            } else if (req.body.data[2][k] === "integer[]" || req.body.data[2][k] === "numeric[]") {
                                                stringType = "integer[]";
                                            } else if (req.body.data[2][k] === "boolean[]") {
                                                stringType = "boolean[]";
                                            } else {
                                                stringType = "";
                                            }
                                            if (typeof req.body.data[1][k] === stringType) {
                                                if (stringType === "string") {
                                                    if (params === "") {
                                                        params = params + "'" + req.body.data[1][k] + "'";
                                                    } else {
                                                        params = params + ",'" + req.body.data[1][k] + "'";
                                                    }
                                                } else {
                                                    if (req.body.data[2][k] === "integer" && Number.isInteger(req.body.data[1][k])) {
                                                        if (params === "") {
                                                            params = params + "" + req.body.data[1][k];
                                                        } else {
                                                            params = params + "," + req.body.data[1][k];
                                                        }
                                                    } else if (req.body.data[2][k] === "numeric") {
                                                        if (params === "") {
                                                            params = params + "" + req.body.data[1][k];
                                                        } else {
                                                            params = params + "," + req.body.data[1][k];
                                                        }
                                                    } else {
                                                        checkBeforeReq = false;
                                                    }
                                                }
                                            } else if (typeof req.body.data[1][k] === req.body.data[2][k]) {
                                                if (params === "") {
                                                    params = params + req.body.data[1][k];
                                                } else {
                                                    params = params + "," + req.body.data[1][k];
                                                }
                                            } else if (stringType === "string[]") {
                                                let allString = true;
                                                let paramsFinal = "";
                                                if (Array.isArray(req.body.data[1][k])) {
                                                    for (let a = 0; a < req.body.data[1][k].length; a++) {
                                                        let paramsArray = "";
                                                        for (let j = 0; j < req.body.data[1][k][a].length; j++) {
                                                            if (typeof req.body.data[1][k][a][j] !== "string") {
                                                                allString = false;
                                                                checkBeforeReq = false;
                                                            } else {
                                                                if (paramsArray === "") {
                                                                    paramsArray = "'" + req.body.data[1][k][a][j] + "'";
                                                                } else {
                                                                    paramsArray = paramsArray + ",'" + req.body.data[1][k][a][j] + "'";
                                                                }
                                                            }
                                                        }
                                                        if (paramsFinal === "") {
                                                            paramsFinal = "[" + paramsArray + "]";
                                                        } else {
                                                            paramsFinal = paramsFinal + ",[" + paramsArray + "]";
                                                        }
                                                    }
                                                    if (allString) {
                                                        if (params === "") {
                                                            params = "Array[" + paramsFinal + "]";
                                                        } else {
                                                            params = params + "," + "Array[" + paramsFinal + "]";
                                                        }
                                                    }
                                                } else {
                                                    checkBeforeReq = false;
                                                }
                                            } else if (stringType === "integer[]") {
                                                let allInteger = true;
                                                let paramsFinal = "";
                                                if (Array.isArray(req.body.data[1][k])) {
                                                    for (let a = 0; a < req.body.data[1][k].length; a++) {
                                                        let paramsArray = "";
                                                        for (let j = 0; j < req.body.data[1][k][a].length; j++) {
                                                            if (typeof req.body.data[1][k][a][j] !== "number") {
                                                                allInteger = false;
                                                                checkBeforeReq = false;
                                                            } else {
                                                                if (Number.isInteger(req.body.data[1][k][a][j])) {
                                                                    if (paramsArray === "") {
                                                                        paramsArray = "" + req.body.data[1][k][a][j];
                                                                    } else {
                                                                        paramsArray = paramsArray + "," + req.body.data[1][k][a][j];
                                                                    }
                                                                } else if (req.body.data[2][k] === "numeric[]") {
                                                                    if (paramsArray === "") {
                                                                        paramsArray = "" + req.body.data[1][k][a][j];
                                                                    } else {
                                                                        paramsArray = paramsArray + "," + req.body.data[1][k][a][j];
                                                                    }
                                                                } else {
                                                                    checkBeforeReq = false;
                                                                }
                                                            }
                                                        }
                                                        if (paramsFinal === "") {
                                                            paramsFinal = "[" + paramsArray + "]";
                                                        } else {
                                                            paramsFinal = paramsFinal + ",[" + paramsArray + "]";
                                                        }
                                                    }
                                                    if (allInteger) {
                                                        if (params === "") {
                                                            params = "Array[" + paramsFinal + "]";
                                                        } else {
                                                            params = params + "," + "Array[" + paramsFinal + "]";
                                                        }
                                                    }
                                                } else {
                                                    checkBeforeReq = false;
                                                }
                                            } else if (stringType === "boolean[]") {
                                                let allBoolean = true;
                                                let paramsFinal = "";
                                                if (Array.isArray(req.body.data[1][k])) {
                                                    for (let a = 0; a < req.body.data[1][k].length; a++) {
                                                        let paramsArray = "";
                                                        for (let j = 0; j < req.body.data[1][k][a].length; j++) {
                                                            if (typeof req.body.data[1][k][a][j] !== "boolean") {
                                                                allBoolean = false;
                                                                checkBeforeReq = false;
                                                            } else {
                                                                if (paramsArray === "") {
                                                                    paramsArray = "" + req.body.data[1][k][a][j];
                                                                } else {
                                                                    paramsArray = paramsArray + "," + req.body.data[1][k][a][j];
                                                                }
                                                            }
                                                        }
                                                        if (paramsFinal === "") {
                                                            paramsFinal = "[" + paramsArray + "]";
                                                        } else {
                                                            paramsFinal = paramsFinal + ",[" + paramsArray + "]";
                                                        }
                                                    }
                                                    if (allBoolean) {
                                                        if (params === "") {
                                                            params = "Array[" + paramsFinal + "]";
                                                        } else {
                                                            params = params + "," + "Array[" + paramsFinal + "]";
                                                        }
                                                    }
                                                } else {
                                                    checkBeforeReq = false;
                                                }
                                            } else {
                                                //check wrong data
                                                checkBeforeReq = false;
                                            }
                                        }

                                        if (checkBeforeReq) {
                                            let callStored = req.body.data[0] + "(" + params + ")";
                                            new Logger().info("request " + callStored);
                                            client.query("SELECT * FROM " + callStored, (err, result) => {
                                                if (result) {
                                                    new Logger().info("response " + req.body.data[0] + " : " + JSON.stringify(result.rows));
                                                    res.status(200).send(result.rows);
                                                } else {
                                                    console.log("err store", err);
                                                    var josnError = { code: stroedError, message: req.body.data[0] + " " + err.message };
                                                    new Logger().error(req.body.data[0] + " : " + JSON.stringify(josnError) + " Query : " + callStored);
                                                    res.status(200).send(josnError);
                                                }
                                            });
                                        } else {
                                            let josnError = { code: typeError, message: typeErrorMess };
                                            new Logger().error(req.body.data[0] + " : " + JSON.stringify(josnError));
                                            res.status(200).send(josnError);
                                        }
                                        return;
                                    } else {
                                        let josnError = { code: formatError, message: formatErrorMess };
                                        new Logger().error(req.body.data[0] + " : " + JSON.stringify(josnError) + " Error at : " + JSON.stringify(req.body.data[2]));
                                        res.status(200).send(josnError);
                                        return;
                                    }
                                }
                            }
                        } else {
                            let josnError = { code: formatError, message: formatErrorMess };
                            new Logger().error(req.body.data[0] + " : " + JSON.stringify(josnError) + " Error at : data.length < 3");
                            res.status(200).send(josnError);
                            return;
                        }
                    }
                }
                let josnError = { code: formatError, message: formatErrorMess };
                new Logger().error(req.body.data[0] + " : " + JSON.stringify(josnError));
                res.status(200).send(josnError);
            } else {
                console.log("err store", err);
                let josnError = { code: stroedError, message: req.body.data[0] + " " + err.message };
                new Logger().error(req.body.data[0] + " : " + JSON.stringify(josnError) + " Query : parameter_check2('" + req.body.data[0] + "')");
                res.status(200).send(josnError);
            }
        });
    } else {
        let josnError = { code: apiKeyError, message: formatApiKeyErrorMess };
        new Logger().error(req.body.data[0] + " : " + JSON.stringify(josnError));
        res.status(200).send(josnError);
    }
});
app.listen(port, () => console.log(`listening at port : ${port}`));
