using System;
using System.Collections.Generic;
using System.IO;
using System.Threading;

namespace SfsAccess.Biometrics
{
    public sealed class DigitalPersonaCapture : DPFP.Capture.EventHandler
    {
        private readonly bool _enroll;
        private readonly ManualResetEvent _completed = new ManualResetEvent(false);
        private readonly object _sync = new object();
        private readonly DPFP.Capture.Capture _capturer;
        private readonly DPFP.Processing.Enrollment _enroller;
        private readonly List<KeyValuePair<string, DPFP.Template>> _templates = new List<KeyValuePair<string, DPFP.Template>>();
        private string _result;
        private Exception _error;

        private DigitalPersonaCapture(bool enroll, string candidateRows)
        {
            _enroll = enroll;
            if (enroll)
            {
                _enroller = new DPFP.Processing.Enrollment();
            }
            else
            {
                LoadTemplates(candidateRows);
            }

            _capturer = new DPFP.Capture.Capture();
            _capturer.EventHandler = this;
        }

        public static string Enroll()
        {
            return new DigitalPersonaCapture(true, null).Run();
        }

        public static string Identify(string candidateRows)
        {
            return new DigitalPersonaCapture(false, candidateRows).Run();
        }

        private void LoadTemplates(string rows)
        {
            if (String.IsNullOrEmpty(rows))
                throw new InvalidOperationException("No hay huellas enroladas en la base de datos.");

            foreach (string row in rows.Split(new[] { '\r', '\n' }, StringSplitOptions.RemoveEmptyEntries))
            {
                string[] fields = row.Split(new[] { '\t' }, 2);
                if (fields.Length != 2 || String.IsNullOrWhiteSpace(fields[0]) || String.IsNullOrWhiteSpace(fields[1]))
                    continue;

                try
                {
                    byte[] data = Convert.FromBase64String(fields[1]);
                    using (MemoryStream stream = new MemoryStream(data))
                    {
                        _templates.Add(new KeyValuePair<string, DPFP.Template>(fields[1], new DPFP.Template(stream)));
                    }
                }
                catch
                {
                    // Los registros antiguos que no son plantillas del SDK no pueden compararse.
                }
            }

        }

        private string Run()
        {
            try
            {
                _capturer.StartCapture();
                if (!_completed.WaitOne(TimeSpan.FromSeconds(60)))
                    throw new TimeoutException("No se recibió una captura válida en 60 segundos.");
                if (_error != null)
                    throw _error;
                return _result;
            }
            finally
            {
                try { _capturer.StopCapture(); } catch { }
                _capturer.Dispose();
                _completed.Dispose();
            }
        }

        public void OnComplete(object capture, string readerSerialNumber, DPFP.Sample sample)
        {
            lock (_sync)
            {
                if (_completed.WaitOne(0))
                    return;

                try
                {
                    DPFP.Processing.DataPurpose purpose = _enroll
                        ? DPFP.Processing.DataPurpose.Enrollment
                        : DPFP.Processing.DataPurpose.Verification;
                    DPFP.Capture.CaptureFeedback feedback = DPFP.Capture.CaptureFeedback.None;
                    DPFP.FeatureSet features = new DPFP.FeatureSet();
                    new DPFP.Processing.FeatureExtraction().CreateFeatureSet(sample, purpose, ref feedback, ref features);
                    if (feedback != DPFP.Capture.CaptureFeedback.Good)
                        return;

                    if (_enroll)
                    {
                        _enroller.AddFeatures(features);
                        if (_enroller.TemplateStatus == DPFP.Processing.Enrollment.Status.Ready)
                        {
                            using (MemoryStream stream = new MemoryStream())
                            {
                                _enroller.Template.Serialize(stream);
                                _result = Convert.ToBase64String(stream.ToArray());
                            }
                            _completed.Set();
                        }
                        else if (_enroller.TemplateStatus == DPFP.Processing.Enrollment.Status.Failed)
                        {
                            _enroller.Clear();
                        }
                    }
                    else
                    {
                        DPFP.Verification.Verification verifier = new DPFP.Verification.Verification();
                        foreach (KeyValuePair<string, DPFP.Template> item in _templates)
                        {
                            DPFP.Verification.Verification.Result result = new DPFP.Verification.Verification.Result();
                            verifier.Verify(features, item.Value, ref result);
                            if (result.Verified)
                            {
                                _result = item.Key;
                                _completed.Set();
                                return;
                            }
                        }

                        _result = String.Empty;
                        _completed.Set();
                    }
                }
                catch (Exception exception)
                {
                    _error = exception;
                    _completed.Set();
                }
            }
        }

        public void OnFingerGone(object capture, string readerSerialNumber) { }
        public void OnFingerTouch(object capture, string readerSerialNumber) { }
        public void OnReaderConnect(object capture, string readerSerialNumber) { }
        public void OnReaderDisconnect(object capture, string readerSerialNumber) { }
        public void OnSampleQuality(object capture, string readerSerialNumber, DPFP.Capture.CaptureFeedback feedback) { }
    }
}