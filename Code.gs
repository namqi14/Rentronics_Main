// Global variables for the spreadsheet and sheets
var wsData = SpreadsheetApp.getActiveSpreadsheet();
var url_base = wsData.getUrl().replace(/edit$/, '');
var bankStatement = wsData.getSheetByName("Data Bank Statement");
var inv = wsData.getSheetByName("Inv"); // Reintroduced "Inv" sheet
var invID = inv.getSheetId(); // Reintroduced "Inv" ID
var bankStatementTitle = bankStatement.getRange(1, 1, 1, bankStatement.getLastColumn()).getValues()[0];
var parentFolderId = "1GKKsQQnNsPdGnwPrPtrKXcoJanS0YkHU";
var parentFolderName = "Owais Audit 2024";
var parentFolder = DriveApp.getFolderById(parentFolderId);

// Constants for column indices, calculated once and reused
var refNoColNo = bankStatementTitle.indexOf("No To Print") + 1;
var fileNameColNo = bankStatementTitle.indexOf("File Name to Print") + 1;
var clientNameColNo = bankStatementTitle.indexOf("Client Short Name") + 1;

function printInvoices() {
  // Get the starting row for printing invoices
  var startRow = bankStatement.getRange(2, refNoColNo, 1, 1).getValue(); // Assuming the row number to start is stored in the second row
  var lastRow = bankStatement.getLastRow();

  for (var i = startRow; i <= lastRow; i++) {
    // Get the file name for the invoice to print
    var invoicePdfName = bankStatement.getRange(i, fileNameColNo).getValue();

    // Assuming the PDF needs to be saved in a client-specific folder, determined by "Client Short Name"
    var clientName = bankStatement.getRange(i, clientNameColNo).getValue();
    var clientFolder = ensureFolderExists(clientName, parentFolder);

    // Save the invoice in PDF format
    savePDF(invoicePdfName, clientFolder);
  }
}

function ensureFolderExists(folderName, parentFolder) {
  var folders = parentFolder.getFoldersByName(folderName);
  if (folders.hasNext()) {
    return folders.next();
  } else {
    return parentFolder.createFolder(folderName);
  }
}

function savePDF(pdfName, pdfFolder) {
  var url_ext = 'export?exportFormat=pdf&format=pdf'
    + '&gid=' + invID
    + '&size=A4'
    + '&landscape=false'
    + '&scale=4'
    + '&top_margin=0.50'
    + '&bottom_margin=0.50'
    + '&left_margin=0.50'
    + '&right_margin=0.50'
    + '&sheetnames=false'
    + '&printtitle=false'
    + '&horizontal_alignment=CENTER'
    + '&pagenumbers=false'
    + '&gridlines=false'
    + '&fzr=true';
  var url_options = {headers: {'Authorization': 'Bearer ' + ScriptApp.getOAuthToken()}, muteHttpExceptions: true};
  var response = UrlFetchApp.fetch(url_base + url_ext, url_options);
  if (response.getResponseCode() == 429) {
    // Hit rate limits, retry after delay
    Utilities.sleep(10000); // Sleep 10 seconds
    response = UrlFetchApp.fetch(url_base + url_ext, url_options); // Retry fetching
  }
  var blob = response.getBlob().getAs('application/pdf').setName(pdfName + '.pdf');
  pdfFolder.createFile(blob);
}

// //function to send email
// function sendEmail(plName,invName){

//   var titleColNo = coInfoTitle.indexOf("Email Title")+1;
//   var title = coInfo.getRange(2,titleColNo,1,1).getValue();
//   var paymentColNo = coInfoTitle.indexOf("Payment Amount")+1;
//   var paymentInfo = coInfo.getRange(2,paymentColNo,1,1).getValue();
//   var dueDateColNo = coInfoTitle.indexOf("Due Date")+1;
//   var dueDateInfo = coInfo.getRange(2,dueDateColNo,1,1).getValue();
//   var content =
//     {
//       payment: paymentInfo,
//       dueDate: dueDateInfo
//     };

//   //Get Email Adress without blank array
//   var emailAddressColNo = coInfoTitle.indexOf("Email to")+1;
//   var emailAddressFlag = coInfo.getRange(2,emailAddressColNo,emailLastRow,1).getValues().map(function(o){return o[0]});
//   var emailAddress = [];
//   var i=0;
//   while(emailAddressFlag[i]!=""){
//     emailAddress[i]=emailAddressFlag[i];
//     i++;
//   }
//   var ccEmailAddressColNo = coInfoTitle.indexOf("Email CC")+1;
//   var ccEmailAddressFlag = coInfo.getRange(2,ccEmailAddressColNo,emailLastRow,1).getValues().map(function(o){return o[0]});
//   var ccEmailAddress = [];
//   var i=0;
//   while(ccEmailAddressFlag[i]!=""){
//     ccEmailAddress[i]=ccEmailAddressFlag[i];
//     i++;
//   }

//   //Get PL PDF Files
//   var plPDFIter = DriveApp.searchFiles('title contains "'+plName+'" and trashed=false');
//   while (plPDFIter.hasNext()){
//     var testPlFile = plPDFIter.next();
//     if (testPlFile.getName() == plName+".pdf"){
//       var plPDFID = testPlFile.getId();
//       var plPDF = DriveApp.getFileById(plPDFID);
//       break;
//       }
//   }
//   Logger.log(plPDF.getId());

//   //Get Inv PDF Files
//   var invPDFIter = DriveApp.searchFiles('title contains "'+invName+'" and trashed=false');
//   while (invPDFIter.hasNext()){
//     var testInvFile = invPDFIter.next(); 
//     if (testInvFile.getName() == invName+".pdf"){
//       var invPDFID = testInvFile.getId();
//       var invPDF = DriveApp.getFileById(invPDFID);
//       break;
//       }
//   }
//   Logger.log(invPDF.getId());
//   //var pdfFiles = [plPDF,invPDF];
  

//   var templ = HtmlService.createTemplateFromFile('emailTemplate');
//   templ.content = content;
//   var message = templ.evaluate().getContent();

//   var allEmailAddress=emailAddress[0];
//   var allCCEmailAddress=ccEmailAddress[0];
//   i=0;
//   for(i=1;i<emailAddress.length;i++){
//     allEmailAddress = allEmailAddress + "," + emailAddress[i];
//   }
//   Logger.log(allEmailAddress);

//   for(i=1;i<ccEmailAddress.length;i++){
//     allCCEmailAddress = allCCEmailAddress + "," + ccEmailAddress[i];
//   }
//   Logger.log(allCCEmailAddress);


//   MailApp.sendEmail({
//     to: allEmailAddress,
//     cc: allCCEmailAddress,
//     subject: title,
//     htmlBody: message,
//     attachments: [plPDF,invPDF]
//   });



//   Logger.log(title);
  
// }


// //function to create whatsapp link in google sheet
// function createWhatsappLink(coName){

//   //get whatsapp number(s)
//   var hpColNo = coInfoTitle.indexOf("Whatsapp")+1;
//   var hpFlag = coInfo.getRange(2,hpColNo,emailLastRow-1,1).getValues().map(function(o){return o[0]});;
//   var hp = [];
//   var i=0;
//   while(hpFlag[i]!=""){
//     hp[i]=hpFlag[i].toString();
//     i++;
//   }
//   var noOfHp = hp.length;

//   //get invoice month
//   var invoiceMonthColNo = coInfoTitle.indexOf("Invoice Month")+1;
//   var invoiceMonth = coInfo.getRange(2,invoiceMonthColNo,1,1).getValue();

//   //get payment amount
//   var amountColNo = coInfoTitle.indexOf("Payment Amount")+1;
//   var amount = coInfo.getRange(2,amountColNo,1,1).getValue();

//   for (i=0;i<noOfHp;i++){

//     var whatsappNewRow = whatsappSheet.getLastRow()+1;

//     //set company name in send whatsapp sheet
//     var coNameColNo = whatsappSheetTitle.indexOf("Company Name")+1;
//     whatsappSheet.getRange(whatsappNewRow,coNameColNo,1,1).setValue(coName);

//     //set whatsapp number in Send Whatsapp Sheet
//     var whatsappColNo = whatsappSheetTitle.indexOf("Whatsapp")+1;
//     whatsappSheet.getRange(whatsappNewRow,whatsappColNo,1,1).setValue(hp[i]);

//     //set invoice month in Send Whatsapp Sheet
//     var wsInvoiceMonthColNo = whatsappSheetTitle.indexOf("Invoice Month")+1;
//     whatsappSheet.getRange(whatsappNewRow,wsInvoiceMonthColNo,1,1).setValue(invoiceMonth);

//     //set payment Amount in Send Whatsapp Sheet
//     var wsAmountColNo = whatsappSheetTitle.indexOf("Payment Amount")+1;
//     whatsappSheet.getRange(whatsappNewRow,wsAmountColNo,1,1).setValue(amount);

//     //set whatsapp message in Send Whatsapp Sheet
//     var wsMessage = "Outstanding " + coName + "-Owais\n";
//     wsMessage = wsMessage + "\n";
//     wsMessage = wsMessage + "*" + invoiceMonth + " - " + amount + "*\n";
//     wsMessage = wsMessage + "Total Outstanding - *" + amount + "*\n";
//     wsMessage = wsMessage + "\n";
//     wsMessage = wsMessage + "Kindly settle at earliest convinience.";
//     var wsMessageColNo = whatsappSheetTitle.indexOf("Message")+1;
//     whatsappSheet.getRange(whatsappNewRow,wsMessageColNo,1,1).setValue(wsMessage);

//     //set whatsapp hyperlink in Send Whatsapp Sheet
//     var wsHyperlink = '=HYPERLINK("https://api.whatsapp.com/send?phone=+"&B'+whatsappNewRow.toString()+'&"&text="&E'+whatsappNewRow.toString()
//                       +',"Send Whatsapp to "&A'+whatsappNewRow.toString()+')'
//     var wsHyperlinkColNo = whatsappSheetTitle.indexOf("Whatsapp Link")+1;
//     whatsappSheet.getRange(whatsappNewRow,wsHyperlinkColNo,1,1).setFormula(wsHyperlink);

//     //set checkboxes in Send Whatsapp Sheet
//     var sentColNo = whatsappSheetTitle.indexOf("Sent?")+1;
//     whatsappSheet.getRange(whatsappNewRow,sentColNo,1,1).insertCheckboxes();

//   }

//   Logger.log(hp);
//   Logger.log(noOfHp);
// }

/*
//function for testing
function testcreateWhatsappLink(){

  //get company name
  var parentFolderNameColNo = coInfoTitle.indexOf("Parent Folder Name")+1;
  var parentFolderName = coInfo.getRange(2,parentFolderNameColNo,1,1).getValue();

  //get whatsapp number(s)
  var hpColNo = coInfoTitle.indexOf("Whatsapp")+1;
  var hpFlag = coInfo.getRange(2,hpColNo,emailLastRow-1,1).getValues().map(function(o){return o[0]});;
  var hp = [];
  var i=0;
  while(hpFlag[i]!=""){
    hp[i]=hpFlag[i].toString();
    i++;
  }
  var noOfHp = hp.length;

  //get invoice month
  var invoiceMonthColNo = coInfoTitle.indexOf("Invoice Month")+1;
  var invoiceMonth = coInfo.getRange(2,invoiceMonthColNo,1,1).getValue();

  //get invoice month
  var amountColNo = coInfoTitle.indexOf("Payment Amount")+1;
  var amount = coInfo.getRange(2,amountColNo,1,1).getValue();

  for (i=0;i<noOfHp;i++){

    var whatsappNewRow = whatsappSheet.getLastRow()+1;

    //set company name in send whatsapp sheet
    var coNameColNo = whatsappSheetTitle.indexOf("Company Name")+1;
    whatsappSheet.getRange(whatsappNewRow,coNameColNo,1,1).setValue(parentFolderName);

    //set whatsapp number in Send Whatsapp Sheet
    var whatsappColNo = whatsappSheetTitle.indexOf("Whatsapp")+1;
    whatsappSheet.getRange(whatsappNewRow,whatsappColNo,1,1).setValue(hp[i]);

    //set invoice month in Send Whatsapp Sheet
    var wsInvoiceMonthColNo = whatsappSheetTitle.indexOf("Invoice Month")+1;
    whatsappSheet.getRange(whatsappNewRow,wsInvoiceMonthColNo,1,1).setValue(invoiceMonth);

    //set payment Amount in Send Whatsapp Sheet
    var wsAmountColNo = whatsappSheetTitle.indexOf("Payment Amount")+1;
    whatsappSheet.getRange(whatsappNewRow,wsAmountColNo,1,1).setValue(amount);

    //set whatsapp message in Send Whatsapp Sheet
    var wsMessage = "Outstanding " + parentFolderName + "-Owais\n";
    wsMessage = wsMessage + "\n";
    wsMessage = wsMessage + "*" + invoiceMonth + " - " + amount + "*\n";
    wsMessage = wsMessage + "Total Outstanding - *" + amount + "*\n";
    wsMessage = wsMessage + "\n";
    wsMessage = wsMessage + "Kindly settle at earliest convinience.";
    var wsMessageColNo = whatsappSheetTitle.indexOf("Message")+1;
    whatsappSheet.getRange(whatsappNewRow,wsMessageColNo,1,1).setValue(wsMessage);

    //set whatsapp hyperlink in Send Whatsapp Sheet
    var wsHyperlink = '=HYPERLINK("https://api.whatsapp.com/send?phone=+"&B'+whatsappNewRow.toString()+'&"&text="&E'+whatsappNewRow.toString()
                      +',"Send Whatsapp to "&A'+whatsappNewRow.toString()+')'
    var wsHyperlinkColNo = whatsappSheetTitle.indexOf("Whatsapp Link")+1;
    whatsappSheet.getRange(whatsappNewRow,wsHyperlinkColNo,1,1).setFormula(wsHyperlink);

  }

  Logger.log(hp);
  Logger.log(noOfHp);
}
*/


