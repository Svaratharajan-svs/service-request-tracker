"use client";

import { ReactNode, useEffect, useRef, useState } from "react";

import styles from "./BadgeSelect.module.css";

export type BadgeSelectOption = {
    value: string;
    label: string;
    /** Rich content (e.g. a badge). Falls back to the label. */
    content?: ReactNode;
};

type Props = {
    id?: string;
    value: string;
    onChange: (value: string) => void;
    options: BadgeSelectOption[];
    ariaLabel: string;
};

export default function BadgeSelect({
    id,
    value,
    onChange,
    options,
    ariaLabel,
}: Props) {
    const [open, setOpen] = useState(false);
    const [activeIndex, setActiveIndex] = useState(0);
    const rootRef = useRef<HTMLDivElement>(null);

    const selectedIndex = Math.max(
        0,
        options.findIndex((option) => option.value === value),
    );
    const selected = options[selectedIndex];

    // Close when clicking outside
    useEffect(() => {
        function handleClick(event: MouseEvent) {
            if (!rootRef.current?.contains(event.target as Node)) {
                setOpen(false);
            }
        }
        document.addEventListener("mousedown", handleClick);
        return () => document.removeEventListener("mousedown", handleClick);
    }, []);

    function openMenu() {
        setActiveIndex(selectedIndex);
        setOpen(true);
    }

    function choose(index: number) {
        onChange(options[index].value);
        setOpen(false);
    }

    function handleKeyDown(event: React.KeyboardEvent) {
        if (event.key === "Escape") {
            setOpen(false);
            return;
        }

        if (!open) {
            if (["ArrowDown", "ArrowUp", "Enter", " "].includes(event.key)) {
                event.preventDefault();
                openMenu();
            }
            return;
        }

        if (event.key === "ArrowDown") {
            event.preventDefault();
            setActiveIndex((i) => Math.min(i + 1, options.length - 1));
        } else if (event.key === "ArrowUp") {
            event.preventDefault();
            setActiveIndex((i) => Math.max(i - 1, 0));
        } else if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            choose(activeIndex);
        }
    }

    return (
        <div className={styles.root} ref={rootRef} onKeyDown={handleKeyDown}>
            <button
                id={id}
                type="button"
                className={`${styles.trigger} ${open ? styles.triggerOpen : ""}`}
                aria-haspopup="listbox"
                aria-expanded={open}
                aria-label={ariaLabel}
                onClick={() => (open ? setOpen(false) : openMenu())}
            >
                <span className={styles.value}>
                    {selected.content ?? selected.label}
                </span>
                <svg
                    className={`${styles.chevron} ${open ? styles.chevronOpen : ""}`}
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2.5"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    aria-hidden="true"
                >
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </button>

            {open && (
                <ul className={styles.menu} role="listbox" aria-label={ariaLabel}>
                    {options.map((option, index) => (
                        <li
                            key={option.value}
                            role="option"
                            aria-selected={option.value === value}
                            className={`${styles.option} ${index === activeIndex ? styles.optionActive : ""
                                }`}
                            onMouseEnter={() => setActiveIndex(index)}
                            onClick={() => choose(index)}
                        >
                            <span className={styles.optionContent}>
                                {option.content ?? option.label}
                            </span>
                            {option.value === value && (
                                <svg
                                    className={styles.check}
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2.5"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    aria-hidden="true"
                                >
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
