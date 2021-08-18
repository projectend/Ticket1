const winston = require("winston");
const { format, transports, createLogger } = winston;
const { timestamp, splat, simple, combine, printf, label } = format;
const fs = require("fs");
const path = require("path");
const logDir = "../log";

// Create the log directory if it does not exist
if (!fs.existsSync(logDir)) {
    fs.mkdirSync(logDir);
}

class Logger {
    /**
     * Logger
     * @param {Object} options
     * @param {Object} options.label Log label
     * @return {winston.Logger}
     */
    constructor(options) {
        let filename;
        const d = new Date();
        const ye = new Intl.DateTimeFormat("en", { year: "numeric" }).format(d);
        const mo = new Intl.DateTimeFormat("en", { month: "numeric" }).format(d);
        const da = new Intl.DateTimeFormat("en", { day: "2-digit" }).format(d);
        const dateCurrent = da + "-" + mo + "-" + ye;
        filename = path.join(logDir, `results ${dateCurrent}.log`);

        // Set default options
        // options = options || {};
        // options = Object.assign({ label: "app" }, options);

        // let formatter = printf((info) => {
        //     if (typeof info.message === "object") {
        //         info.message = JSON.stringify(info.message);
        //     }
        //     return `${info.timestamp} [${info.level}]: ` + `${path.basename(process.mainModule.filename)} - ${info.message}`;
        // });

        let loggerTransports = [
            // new transports.Console({
            //     level: process.env.LOG_LEVEL || "info",
            //     format: combine(label({ label: path.basename(process.mainModule.filename) }), timestamp(), splat(), simple(), formatter),
            // }),
            new transports.File({ filename }),
        ];

        return createLogger({
            level: process.env.NODE_ENV === "development" ? "debug" : "info",
            transports: loggerTransports,
            format: format.combine(
                format.label({ label: path.basename(process.mainModule.filename) }),
                format.timestamp({
                    format: "YYYY-MM-DD HH:mm:ss",
                }),
                format.json()
            ),
        });
    }
}

module.exports = Logger;
