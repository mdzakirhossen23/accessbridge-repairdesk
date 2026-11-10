# AccessBridge RepairDesk

Accessible repair-management prototype for the Ctrl+V Hackathon.

- [Live demo](https://accessbridge.baharsoftbd.com/)
- [Demo video](https://youtu.be/uM_qSGsYVlE)
- [Devpost](https://devpost.com/software/accessbridge-repairdesk)

## Features

Multilingual interface; English and Bangla AI repair guidance; read-aloud with matching browser voices; larger text and high contrast; customer and device intake, jobs, technicians, inventory, purchases, suppliers, invoices, payments, delivery/warranty and expenses. Records stay in browser local storage. Sample records are illustrative.

## Source status

`index.html` is the current deployed frontend, including its CSS and JavaScript. The deployed PHP AI endpoint is pending export from the hosting account; this repository is not yet a complete AI deployment. Credentials will never be committed. The page also references an existing BaharSoftBD presence script and externally hosted illustrative images.

## Run

Serve this directory with a local web server for the browser UI. AI requires `api-chat.php` on a PHP server with cURL, mbstring, HTTPS access to Groq, and a separately configured secret outside the public web directory. Until that backend is added, use the live demo for AI.

Read-aloud requires an installed browser voice for the selected language. Test speaker output before recording. Local records are browser-specific; export backups before clearing site data.

## Privacy and limits

AI questions are sent through the server to Groq. Do not include private information. AI guidance is not a diagnosis or verified repair quote. Price filtering reduces unverified pricing claims; it is not a guarantee of factual accuracy. Full screen-reader, customer usability, hosting-log and provider-retention audits remain outstanding.

## Reused work

The desktop window, register forms, image editing, archive/restore, CSV and printing were adapted from existing BaharSoftBD applications. An existing AI endpoint was reused. Repair workflows, accessible controls, multilingual repair guidance, pricing filters, speech fixes and build notes were developed in the current iteration. No claim is made that all components were created from scratch.
