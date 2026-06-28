const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('smtpSettings', {
  load: () => ipcRenderer.invoke('smtp:load'),
  save: (settings) => ipcRenderer.invoke('smtp:save', settings)
});
