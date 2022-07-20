import datetime
import re
import typing
from PyQt5 import QtWidgets
from PyQt5.QtWidgets import (
    QApplication,
    QMainWindow,
    QVBoxLayout,
    QGridLayout,
    QWidget
)
import sys
from pathlib import Path
import os
import json
import requests
import win32com.client
import urllib3
import pprint
import hashlib

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

ENV = 'DEV'
TESTDATE = '2019-09-15'
DEBUG_MAX_COUNT = -1  # use -1 if you want to deactivate max count


# Download current mails from the server and put them into your local Outlook Draft Folder

# noinspection PyAttributeOutsideInit
class AppWindow(QWidget):
    settings_file = 'outlook_interactor_settings.json'
    default_url_base = 'https://nsdb.local'
    routes = {
        'get_mails': '/api/get-waiting-mails',
        'confirm_mails': '/api/confirm-sent-mails'
    }
    settings = {}

    def __init__(self):
        self.outlook_interactor = Interactor()
        super().__init__()
        self.setGeometry(200, 200, 500, 600)
        self.setWindowTitle("Outlook interactor")
        self.load_settings()
        self.layout = QGridLayout()
        self.buttons = {}
        self.fields = {}
        self.output_field = QtWidgets.QTextEdit()
        self.row_counter = 0


        self.add_button('sync_server_to_outlook', 'Get mails from server', self.sync_server_to_outlook)

        self.add_form_row('key', 'Key:')
        self.add_form_row('base_url', 'Url to domain:', self.default_url_base)

        self.add_button('save_to_settings', 'Save to settings', self.save_all_fields_to_settings)

        self.output_field.resize(300, 300)

        self.layout.addWidget(self.output_field, self.row_counter, 0, 2, 2)

        self.setLayout(self.layout)


    def add_button(self, name, label, callback, pos = [None, 0], size=(1, 2)):
        btn = QtWidgets.QPushButton(label)
        btn.clicked.connect(callback)
        btn.resize(150, 30)
        self.buttons[name] = btn

        row = self.row_counter if pos[0] is None else pos[0]
        self.row_counter += 1

        self.layout.addWidget(btn, row, pos[1], size[0], size[1])

    def add_form_row(self, name, label_text, placeholder_text = '', row = None, key = None):
        key = name if key is None else key
        row = self.row_counter if row is None else row
        self.row_counter += 1

        label = QtWidgets.QLabel(label_text)
        self.layout.addWidget(label, row, 0)

        field = QtWidgets.QLineEdit()
        field.setPlaceholderText(placeholder_text)
        field.resize(250,20)
        field.setText(self.settings.get(key, ''))

        self.fields[key] = field
        self.layout.addWidget(field, row, 1)

    def sync_server_to_outlook(self):
        response = self.request_get('get_mails')
        if response.status_code == requests.codes.ok:
            data = response.json()
            mails = data.get('mails', [])
            token = data.get('token')

            status = self.outlook_interactor.put_into_draft_folder(mails)

            self.output_field.setText(str(len(mails)) + ' mails put into draft folder in outlook.')
            self.request_post('confirm_mails', {'status': status, 'token': token})
        elif response.status_code == requests.codes.conflict:
            self.output_field.setText('There are still uncategorized mails in the database')
        else:
            self.output_field.setText('Server responded with error code ' + str(response.status_code))

    def get_url(self, type):
        url_base = self.settings.get('url_base', '')
        url_base = self.default_url_base if len(url_base) == 0 else url_base

        return url_base + self.routes.get(type, '???')

    def save_all_fields_to_settings(self):
        for name, field in self.fields.items():
            self.settings[name] = field.text()
        self.save_settings()

    def save_settings(self):
        folder = self.get_settings_folder()
        Path(folder).mkdir(parents=True, exist_ok=True)
        file = folder + '/' + self.settings_file
        with open(file, 'w') as f:
            json.dump(self.settings, f)

    def save_to_settings(self, key, value):
        self.settings[key] = value
        self.save_settings()

    def get_settings_folder(self):
        return os.getenv('APPDATA') + "/outlook_interactor"

    def load_settings(self):
        folder = self.get_settings_folder()
        file = folder + '/' + self.settings_file
        if Path(file).is_file():
            with open(file) as f:
                self.settings = json.load(f)

    def request_get(self, type) -> requests.Response:
        params = self.get_default_params()
        return requests.get(self.get_url(type), params, verify=self.request_should_be_verified())

    def request_post(self, type, post_params) -> requests.Response:
        params = {**self.get_default_params(), **post_params}
        return requests.post(self.get_url(type), params, verify=self.request_should_be_verified())

    def get_default_params(self):
        params = {'key': self.settings.get('key')}
        if ENV == 'DEV':
            params['testdate'] = TESTDATE

        return params

    def request_should_be_verified(self) -> bool:
        return ENV != 'DEV'


class Interactor:

    def __init__(self):
        self.outlook = win32com.client.Dispatch("Outlook.Application")

    def put_into_draft_folder(self, mails):
        debug_counter = 0
        for mail in mails:
            debug_counter += 1
            if(DEBUG_MAX_COUNT > 0 and (debug_counter > DEBUG_MAX_COUNT)):
                return
            msg = self.outlook.CreateItem(0)  # creates mail, 0 = olMailItem
            msg.Subject = mail['subject']
            # msg.To = mail['mail']
            msg.To = self.convert_to_debug_address(mail['mail'])  # for debug purposes
            msg.HTMLBody = mail['body']
            msg.Close(0)  # Closes and saves, 0 = close and save without asking
        return 'success'

    def convert_to_debug_address(self, original_address):
        return original_address.replace('@edu.sigtuna-example.se', '@inboxkitten.com')

def window():
    app = QApplication(sys.argv)
    win = AppWindow()
    win.show()
    sys.exit(app.exec_())


window()
