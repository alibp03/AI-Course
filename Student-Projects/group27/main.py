"""
پروژه پایانی درس هوش مصنوعی  
عنوان: سیستم تریاژ هوشمند اورژانس با استفاده از Large Language Model
شماره گروه: 27
نام سرگروه: مهسا فلاحی
اعضای گروه:
    مهدی نجفی
    یکتا ذوالفقاری
    نویده ارشدی شقاقی
    آیدا جدی
                                                                               
استاد: سرکار خانم دکتر مریم حاجی اسمعیلی
دانشگاه: تهران مرکز
ترم: مهر ۱۴۰۴ - ۱۴۰۵
"""

import os
import time
from pathlib import Path
from typing import Optional
from datetime import datetime
from enum import Enum
from dotenv import load_dotenv
from openai import OpenAI
import arabic_reshaper
from bidi.algorithm import get_display

# بارگذاری کلید API
load_dotenv()


try:
    from colorama import init, Fore, Style
    init()
    RED = Fore.RED
    YELLOW = Fore.YELLOW
    GREEN = Fore.GREEN
    CYAN = Fore.CYAN
    MAGENTA = Fore.MAGENTA
    RESET = Style.RESET_ALL
except ImportError:
    RED = YELLOW = GREEN = CYAN = MAGENTA = RESET = ""

def fix_text(text):
    if not text:
        return ""
    reshaped_text = arabic_reshaper.reshape(text)
    bidi_text = get_display(reshaped_text)
    return bidi_text

class TriageLevel(Enum):
    RED = "قرمز - احیای فوری (کمتر از ۲ دقیقه)"
    ORANGE = "نارنجی - خیلی فوری (کمتر از ۱۵ دقیقه)"
    YELLOW = "زرد - فوری (کمتر از ۶۰ دقیقه)"
    GREEN = "سبز - غیرفوری (کمتر از ۴ ساعت)"
    BLUE = "آبی - مرده یا غیرقابل احیا"

class EmergencyTriageSystem:
    """سیستم تریاژ هوشمند اورژانس - نسخه نهایی گروه ۲۷"""
    
    def __init__(self):
        api_key = os.getenv("OPENAI_API_KEY")
        if not api_key:
            print("هشدار: کلید API پیدا نشد! از سرور عمومی استفاده می‌شه...")
        
        self.client = OpenAI(
            api_key=api_key or "sk-None",  # برای سرورهای عمومی نیازی به کلید نیست
            base_url="https://api.chatanywhere.tech/v1"
        )
        self.patient = {}
        self.attachments = {}
        self.triage_result = ""
        self.admission_info = ""
        self.final_diagnosis = ""

    def get_patient_info(self) -> None:
        print("\n" + "="*70)
        print(f"{CYAN}    سیستم تریاژ هوشمند اورژانس - نسخه ۲.۱ (گروه ۲۷){RESET}")
        print(f"{MAGENTA}    دانشگاه تهران مرکز - ترم مهر ۱۴۰۴{RESET}")
        print("="*70)

        self.patient = {
            "name": input(f"\n{YELLOW}نام و نام خانوادگی بیمار{RESET}: ").strip() or "مهسا فلاحی",
            "age": input(f"{YELLOW}سن (سال){RESET}: ").strip() or "27",
            "gender": input(f"{YELLOW}جنسیت (مرد/زن){RESET}: ").strip() or "زن",
            "medications": input(f"{YELLOW}داروهای مصرفی فعلی{RESET}: ").strip() or "نورتریپتیلین ۲۵، پروپرانولول ۴۰",
            "history": input(f"{YELLOW}سابقه پزشکی مهم{RESET}: ").strip() or "پرفشاری خون، دیابت نوع ۲",
            "symptoms": input(f"{YELLOW}علائم فعلی بیمار{RESET}: ").strip() or "درد شدید قفسه سینه و دست چپ، تهوع، تعریق سرد، تپش قلب",
            "blood_pressure": input(f"{YELLOW}فشار خون (مثل 120/80){RESET}: ").strip() or "160/100",
            "pulse": input(f"{YELLOW}نبض (تعداد در دقیقه){RESET}: ").strip() or "110",
            "temperature": input(f"{YELLOW}دمای بدن (درجه سانتی‌گراد){RESET}: ").strip() or "36.8"
        }

        print(f"\n{GREEN}فایل‌های تشخیصی (در صورت وجود - Enter برای رد کردن){RESET}")
        mri = input("   مسیر فایل MRI: ").strip()
        ct = input("   مسیر فایل CT اسکن: ").strip()
        ecg = input("   مسیر فایل نوار قلب (ECG): ").strip()
        lab = input("   مسیر فایل آزمایش خون: ").strip()

        if mri: self.attachments["MRI"] = self._read_file(mri)
        if ct: self.attachments["CT"] = self._read_file(ct)
        if ecg: self.attachments["ECG"] = self._read_file(ecg)
        if lab: self.attachments["Lab"] = self._read_file(lab)

    def _read_file(self, path: str) -> str:
        try:
            p = Path(path.strip('"\''))
            if not p.exists():
                return f"[خطا: فایل {p.name} پیدا نشد]"

            if p.suffix.lower() in {".txt", ".csv", ".pdf"}:
                content = p.read_text(encoding="utf-8", errors="ignore")
                preview = content[:600] + ("..." if len(content) > 600 else "")
                return f"{preview}\n(ادامه فایل توسط پزشک قابل مشاهده است)"
            else:
                size = p.stat().st_size
                return f"[فایل ضمیمه: {p.name}] - نوع: {p.suffix.upper()} - حجم: {size/1024:.1f} KB"
        except Exception as e:
            return f"[خطا در خواندن فایل: {str(e)[:50]}]"

    def _build_prompt(self) -> str:
        info = f"""
تاریخ و زمان تریاژ: {datetime.now().strftime('%Y/%m/%d - %H:%M')}
بیمار: {self.patient['name']} | سن: {self.patient['age']} سال | جنسیت: {self.patient.get('gender','نامشخص')}
داروهای مصرفی: {self.patient['medications']}
سابقه پزشکی: {self.patient['history']}
علائم اصلی: {self.patient['symptoms']}
فشار خون: {self.patient['blood_pressure']} mmHg | نبض: {self.patient.get('pulse','نامشخص')} bpm | دما: {self.patient.get('temperature','نامشخص')} °C
"""
        if self.attachments:
            info += "\nفایل‌های تشخیصی ضمیمه شده:\n"
            for typ, content in self.attachments.items():
                info += f"• {typ}: {content.splitlines()[0] if 'خطا' not in content else content}\n"
        else:
            info += "\nهیچ فایل تشخیصی ارسال نشده است.\n"
        return info.strip()

    def triage(self) -> str:
        print(f"\n{RED}در حال انجام تریاژ اولیه توسط هوش مصنوعی...{RESET}")
        time.sleep(2)
        
        prompt = self._build_prompt() + "\n\nشما متخصص تریاژ اورژانس با ۲۰ سال سابقه هستید. فقط بر اساس استاندارد ESI و بدون احساسات، اولویت بیمار را تعیین کنید:\nقرمز / نارنجی / زرد / سبز / آبی\nو دلایل دقیق و پزشکی را به فارسی بنویسید."
        
        response = self.client.chat.completions.create(
            model="gpt-4o-mini",
            messages=[{"role": "user", "content": prompt}],
            temperature=0.1,
            max_tokens=600
        )
        result = response.choices[0].message.content.strip()
        self.triage_result = result
        return result

    def admission(self) -> str:
        print(f"{YELLOW}ارسال اطلاعات به بخش پذیرش بیمارستان...{RESET}")
        time.sleep(1.5)
        
        prompt = self._build_prompt() + f"\n\nنتیجه تریاژ: {self.triage_result}\n\nشما مسئول پذیرش اورژانس هستید. شماره نوبت، زمان تقریبی انتظار، بخش مربوطه و راهنمایی بیمار را به فارسی بنویسید."
        
        response = self.client.chat.completions.create(
            model="gpt-4o-mini",
            messages=[{"role": "user", "content": prompt}],
            temperature=0.0,
            max_tokens=400
        )
        result = response.choices[0].message.content.strip()
        self.admission_info = result
        return result

    def diagnosis(self) -> str:
        print(f"{MAGENTA}مشاوره با پزشک متخصص هوش مصنوعی...{RESET}")
        time.sleep(2)
        
        prompt = self._build_prompt() + f"\n\nنتیجه تریاژ: {self.triage_result}\n\nشما پزشک متخصص اورژانس با مدرک فوق‌تخصص هستید. به فارسی و کاملاً حرفه‌ای پاسخ دهید:\n1. تشخیص‌های افتراقی (حداقل ۳ مورد با احتمال)\n2. تفسیر فایل‌های ارسالی (اگر وجود دارد)\n3. آزمایش‌ها و تصویربرداری‌های پیشنهادی فوری\n4. تصمیم نهایی: بستری در ICU / CCU / بخش / سرپایی / ترخیص\n5. درمان دارویی فوری و دوز دقیق"
        
        response = self.client.chat.completions.create(
            model="gpt-4o-mini",
            messages=[{"role": "user", "content": prompt}],
            temperature=0.3,
            max_tokens=1500
        )
        result = response.choices[0].message.content.strip()
        self.final_diagnosis = result
        return result

    def save_report(self):
        report = f"""
{'='*80}
                 گزارش نهایی تریاژ هوشمند اورژانس
                 گروه ۲۷ - دانشگاه تهران مرکز
{'='*80}
{self._build_prompt()}

┌─ نتیجه تریاژ ───────────────────────────────
{self.triage_result}

┌─ اطلاعات پذیرش ─────────────────────────────
{self.admission_info}

┌─ تشخیص و درمان نهایی ───────────────────────
{self.final_diagnosis}

{'='*80}
تاریخ تولید گزارش: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}
پروژه پایانی درس هوش مصنوعی - استاد: دکتر مریم حاجی اسمعیلی
{'='*80}
"""
        filename = f"گزارش_تریاژ_{self.patient['name'].replace(' ', '_')}_{datetime.now().strftime('%Y%m%d_%H%M')}.txt"
        Path(filename).write_text(report, encoding='utf-8')
        print(f"\n{GREEN}گزارش کامل در فایل {filename} ذخیره شد ✅{RESET}")

    def run(self):
        try:
            self.get_patient_info()
            print("\n" + "─"*80)
            print(f"{CYAN}خلاصه اطلاعات وارد شده:{RESET}")
            print(self._build_prompt())
            print("─"*80 + "\n")

            print(self.triage())
            print("\n" + "═"*80)
            print(self.admission())
            print("\n" + "═"*80)
            print(self.diagnosis())
            print("\n" + "═"*80)

            self.save_report()

            print(f"{GREEN}\nتریاژ هوشمند با موفقیت انجام شد! گروه ۲۷ بهترینه ❤️{RESET}")
            print(f"{MAGENTA}دانشگاه تهران مرکز - پروژه هوش مصنوعی ۱۴۰۴{RESET}")
            print("═"*80)

        except KeyboardInterrupt:
            print(f"\n\n{RED}اجرای برنامه توسط کاربر متوقف شد.{RESET}")
        except Exception as e:
            print(f"\n{RED}خطای غیرمنتظره: {e}{RESET}")


if __name__ == "__main__":
    system = EmergencyTriageSystem()
    system.run()